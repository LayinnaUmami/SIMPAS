<?php

namespace App\Http\Controllers;

use App\Models\Pelanggaran;
use App\Models\Santri;
use Illuminate\Http\Request;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.pelanggaran');
        }

        $query = $this->scopeSantri(Pelanggaran::with('santri')->cariSantri($request->q));

        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->tingkat);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $pelanggaran = $query->orderByDesc('tanggal')->paginate(10)->withQueryString();

        return view('pelanggaran.index', [
            'pelanggaran' => $pelanggaran,
            'master' => config('simpas.master'),
        ]);
    }

    public function create()
    {
        $this->requireAdmin();

        return view('pelanggaran.form', [
            'pelanggaran' => new Pelanggaran(),
            'master' => config('simpas.master'),
            'santris' => Santri::aktif()->orderBy('nis')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $data = $this->validasi($request);

        $data['poin'] = -abs((int) $data['poin']); // poin pelanggaran SELALU negatif
        $data['is_auto'] = false;
        $data['auto_key'] = null;

        Pelanggaran::create($data);

        return redirect()->route('pelanggaran.index')
            ->with('toast', ['type' => 'success', 'message' => 'Data pelanggaran berhasil disimpan.']);
    }

    public function show(Pelanggaran $pelanggaran)
    {
        $pelanggaran->load('santri');
        abort_unless($pelanggaran->santri, 404);
        $this->authorizeSantri($pelanggaran->santri);

        return view('pelanggaran.show', ['pelanggaran' => $pelanggaran]);
    }

    public function edit(Pelanggaran $pelanggaran)
    {
        $this->requireAdmin();

        return view('pelanggaran.form', [
            'pelanggaran' => $pelanggaran,
            'master' => config('simpas.master'),
            'santris' => Santri::aktif()->orderBy('nis')->get(),
        ]);
    }

    public function update(Request $request, Pelanggaran $pelanggaran)
    {
        $this->requireAdmin();
        $data = $this->validasi($request, $pelanggaran);

        $data['poin'] = -abs((int) $data['poin']);
        $pelanggaran->update($data);

        return redirect()->route('pelanggaran.index')
            ->with('toast', ['type' => 'success', 'message' => 'Data pelanggaran berhasil diperbarui.']);
    }

    public function destroy(Pelanggaran $pelanggaran, Request $request)
    {
        $this->requireAdmin();
        $pelanggaran->delete();

        return redirect()->back()->with('toast', ['type' => 'success', 'message' => 'Data berhasil dipindahkan ke Sampah.']);
    }

    private function validasi(Request $request, ?Pelanggaran $model = null): array
    {
        return $request->validate([
            'santri_id' => ['required', 'exists:santris,id'],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:' . implode(',', config('simpas.master.jenis_pelanggaran'))],
            'tingkat' => ['required', 'in:' . implode(',', config('simpas.master.tingkat_pelanggaran'))],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'poin' => ['required', 'integer', 'between:1,1000'],
        ]);
    }
}
