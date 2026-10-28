<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use App\Services\PeriodResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controller generik untuk 7 jenis aktivitas:
 * Input -> Riwayat -> Detail -> Edit -> Hapus
 */
class ActivityController extends Controller
{
    /** Halaman pilih aktivitas (bagian 9). */
    public function menu()
    {
        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.dashboard');
        }

        $readOnly = auth()->user()?->isSantri() === true;
        $jenis = collect(config('simpas.activities'))->map(function ($cfg, $key) use ($readOnly) {
            $query = $cfg['model']::where('tanggal', today());
            if ($readOnly) {
                $query->where('santri_id', auth()->user()->santri_id);
            }
            $today = $query->count();

            return [
                'key' => $key,
                'label' => $cfg['label'],
                'icon' => $cfg['icon'],
                'color' => $cfg['color'],
                'sessions' => $cfg['sessions'],
                'cadence' => $cfg['cadence'],
                'hari_ini' => $today,
            ];
        });

        return view('aktivitas.menu', [
            'jenis' => $jenis,
            'readOnly' => $readOnly,
        ]);
    }

    /** FORM INPUT - satu tanggal, seluruh santri aktif otomatis termuat. */
    public function index(Request $request, string $type, AttendanceService $attendance)
    {
        $cfg = $this->config($type);
        $tanggal = $this->tanggal($request);

        $santris = $attendance->santriAktif($type);
        $map = $attendance->currentMap($type, $tanggal);

        return view('aktivitas.input', [
            'type' => $type,
            'cfg' => $cfg,
            'tanggal' => $tanggal,
            'santris' => $santris,
            'map' => $map,
            'periode' => PeriodResolver::resolve('hari', ['tanggal' => $tanggal]),
        ]);
    }

    /** SIMPAN (POST) - anti duplikasi: update data lama, bukan insert baru. */
    public function store(Request $request, string $type, AttendanceService $attendance)
    {
        $this->config($type);

        $request->validate([
            'tanggal' => ['required', 'date'],
            'data' => ['required', 'array'],
        ]);

        $rows = $this->flatten($request->input('data', []));

        $hasil = $attendance->save($type, $request->tanggal, $rows);

        // Cukup SATU notifikasi untuk satu kali penyimpanan (bagian 37)
        $message = "Data absensi berhasil disimpan. ({$hasil['created']} baru, {$hasil['updated']} diperbarui)";
        if ($hasil['pelanggaran'] > 0) {
            $message .= " · {$hasil['pelanggaran']} pelanggaran otomatis dibuat.";
        }

        return redirect()
            ->route('aktivitas.input', ['type' => $type, 'tanggal' => $request->tanggal])
            ->with('toast', ['type' => 'success', 'message' => $message]);
    }

    /** RIWAYAT (bagian 18) - default data hari ini. */
    public function riwayat(Request $request, string $type)
    {
        $cfg = $this->config($type);

        if (auth()->user()?->isWali()) {
            return redirect()->route('wali.laporan', ['jenis' => $type]);
        }

        $tanggal = $request->filled('tanggal') ? $request->tanggal : today()->format('Y-m-d');

        $query = $cfg['model']::with('santri')
            ->where('tanggal', $tanggal)
            ->cariSantri($request->q);

        if (auth()->user()?->isSantri()) {
            $query->where('santri_id', auth()->user()->santri_id);
        }

        $records = $query->orderBy('santri_id')->get();

        // Kelompokkan per santri -> "Tanggal Terakhir"
        $rows = [];
        foreach ($records as $rec) {
            $id = $rec->santri_id;
            if (!isset($rows[$id])) {
                $rows[$id] = [
                    'santri' => $rec->santri,
                    'tanggal_terakhir' => $rec->tanggal,
                    'total' => 0,
                    'detail' => [],
                ];
            }
            $rows[$id]['total'] += (int) $rec->poin;
            $rows[$id]['detail'][$rec->sesi] = $rec->status;
        }

        // Tanggal-tanggal yang punya data (untuk filter)
        $dateQuery = $cfg['model']::query()->whereNotNull('tanggal');
        if (auth()->user()?->isSantri()) {
            $dateQuery->where('santri_id', auth()->user()->santri_id);
        }

        $daftarTanggal = $dateQuery
            ->select('tanggal')
            ->distinct()
            ->orderByDesc('tanggal')
            ->limit(60)
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'));

        return view('aktivitas.riwayat', [
            'type' => $type,
            'cfg' => $cfg,
            'tanggal' => $tanggal,
            'rows' => array_values($rows),
            'daftarTanggal' => $daftarTanggal,
            'q' => $request->q,
        ]);
    }

    /** DETAIL (bagian 19). */
    public function detail(Request $request, string $type, int $santriId)
    {
        $cfg = $this->config($type);
        $tanggal = $request->filled('tanggal') ? $request->tanggal : today()->format('Y-m-d');

        $santri = \App\Models\Santri::findOrFail($santriId);
        $this->authorizeSantri($santri);

        $records = $cfg['model']::where('santri_id', $santriId)
            ->where('tanggal', $tanggal)
            ->get();

        $sesi = [];
        foreach ($cfg['sessions'] as $s) {
            $rec = $records->firstWhere('sesi', $s);
            $sesi[$s] = [
                'status' => $rec?->status ?? '-',
                'poin' => $rec?->poin ?? null,
                'ada' => (bool) $rec,
            ];
        }

        $total = (int) $records->sum('poin');

        return view('aktivitas.detail', [
            'type' => $type,
            'cfg' => $cfg,
            'santri' => $santri,
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'total' => $total,
        ]);
    }

    /** EDIT - ubah status per sesi untuk 1 santri pada 1 tanggal. */
    public function edit(Request $request, string $type, int $santriId)
    {
        $this->requireAdmin();
        $cfg = $this->config($type);
        $tanggal = $request->filled('tanggal') ? $request->tanggal : today()->format('Y-m-d');

        $santri = \App\Models\Santri::findOrFail($santriId);
        $records = $cfg['model']::where('santri_id', $santriId)
            ->where('tanggal', $tanggal)
            ->get();

        $nilai = [];
        foreach ($cfg['sessions'] as $s) {
            $nilai[$s] = $records->firstWhere('sesi', $s)?->status;
        }

        return view('aktivitas.edit', [
            'type' => $type,
            'cfg' => $cfg,
            'santri' => $santri,
            'tanggal' => $tanggal,
            'nilai' => $nilai,
        ]);
    }

    public function update(Request $request, string $type, AttendanceService $attendance)
    {
        $this->requireAdmin();
        $this->config($type);

        $request->validate([
            'tanggal' => ['required', 'date'],
            'santri_id' => ['required', 'integer'],
            'data' => ['required', 'array'],
        ]);

        $rows = [];
        foreach ($request->input('data', []) as $sesi => $status) {
            $rows[] = [
                'santri_id' => (int) $request->santri_id,
                'sesi' => $sesi,
                'status' => $status,
            ];
        }

        $attendance->save($type, $request->tanggal, $rows);

        return redirect()
            ->route('aktivitas.riwayat', ['type' => $type, 'tanggal' => $request->tanggal])
            ->with('toast', ['type' => 'success', 'message' => 'Data absensi berhasil diperbarui.']);
    }

    /** HAPUS (soft delete) -> Sampah (bagian 36). */
    public function destroy(string $type, int $santriId, Request $request)
    {
        $this->requireAdmin();
        $cfg = $this->config($type);
        $tanggal = $request->filled('tanggal') ? $request->tanggal : today()->format('Y-m-d');

        $jumlah = $cfg['model']::where('santri_id', $santriId)
            ->where('tanggal', $tanggal)
            ->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => $jumlah > 0
                ? 'Data berhasil dipindahkan ke Sampah.'
                : 'Tidak ada data yang dihapus.',
        ]);
    }

    /* ---------------------------------------------------------------- */

    private function config(string $type): array
    {
        $cfg = config("simpas.activities.{$type}");
        abort_unless($cfg, 404, 'Jenis aktivitas tidak dikenal.');

        return $cfg;
    }

    private function tanggal(Request $request): string
    {
        return $request->filled('tanggal')
            ? $request->tanggal
            : today()->format('Y-m-d');
    }

    /**
     * Bentuk input form: data[santri_id][sesi] = status
     * menjadi array baris siap simpan.
     */
    private function flatten(array $data): array
    {
        $rows = [];
        foreach ($data as $santriId => $sessions) {
            foreach ((array) $sessions as $sesi => $status) {
                $rows[] = [
                    'santri_id' => (int) $santriId,
                    'sesi' => $sesi,
                    'status' => $status,
                ];
            }
        }

        return $rows;
    }
}
