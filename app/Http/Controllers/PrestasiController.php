<?php

namespace App\Http\Controllers;

use App\Models\Prestasi;
use App\Models\Santri;
use Illuminate\Http\Request;

class PrestasiController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.prestasi');
        }

        $query = $this->scopeSantri(Prestasi::with('santri')->cariSantri($request->q));

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->tingkat);
        }

        $prestasi = $query->orderByDesc('tanggal')->paginate(10)->withQueryString();

        return view('prestasi.index', [
            'prestasi' => $prestasi,
            'master' => config('simpas.master'),
        ]);
    }

    public function create()
    {
        $this->requireAdmin();

        return view('prestasi.form', [
            'prestasi' => new Prestasi(),
            'master' => config('simpas.master'),
            'santris' => Santri::aktif()->orderBy('nis')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $data = $this->validasi($request);

        if ($request->hasFile('bukti')) {
            $data['bukti'] = $request->file('bukti')->store('prestasi', 'public');
        }
        $data['poin'] = abs((int) $data['poin']); // poin prestasi positif

        Prestasi::create($data);

        return redirect()->route('prestasi.index')
            ->with('toast', ['type' => 'success', 'message' => 'Data prestasi berhasil disimpan.']);
    }

    public function show(Prestasi $prestasi)
    {
        $prestasi->load('santri');
        abort_unless($prestasi->santri, 404);
        $this->authorizeSantri($prestasi->santri);

        return view('prestasi.show', ['prestasi' => $prestasi]);
    }

    public function edit(Prestasi $prestasi)
    {
        $this->requireAdmin();

        return view('prestasi.form', [
            'prestasi' => $prestasi,
            'master' => config('simpas.master'),
            'santris' => Santri::aktif()->orderBy('nis')->get(),
        ]);
    }

    public function update(Request $request, Prestasi $prestasi)
    {
        $this->requireAdmin();
        $data = $this->validasi($request, $prestasi);

        if ($request->hasFile('bukti')) {
            $data['bukti'] = $request->file('bukti')->store('prestasi', 'public');
        }
        $data['poin'] = abs((int) $data['poin']);

        $prestasi->update($data);

        return redirect()->route('prestasi.index')
            ->with('toast', ['type' => 'success', 'message' => 'Data prestasi berhasil diperbarui.']);
    }

    public function destroy(Prestasi $prestasi, Request $request)
    {
        $this->requireAdmin();
        $prestasi->delete();

        return redirect()->back()->with('toast', ['type' => 'success', 'message' => 'Data berhasil dipindahkan ke Sampah.']);
    }

    private function validasi(Request $request, ?Prestasi $model = null): array
    {
        return $request->validate([
            'santri_id' => ['required', 'exists:santris,id'],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:' . implode(',', config('simpas.master.jenis_prestasi'))],
            'nama_prestasi' => ['required', 'string', 'max:190'],
            'tingkat' => ['required', 'in:' . implode(',', config('simpas.master.tingkat_prestasi'))],
            'penyelenggara' => ['nullable', 'string', 'max:120'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'poin' => ['required', 'integer', 'between:0,1000'],
            'bukti' => ['nullable', 'image', 'max:4096'],
        ]);
    }
}
