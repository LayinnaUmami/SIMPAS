<?php

namespace App\Services;

use App\Models\Pelanggaran;
use App\Models\Prestasi;
use Carbon\Carbon;

/**
 * LAPORAN (bagian 26 - 31)
 *
 * Struktur laporan:
 *   1. Filter periode + search
 *   2. TABEL ABSENSI (data asli per santri per tanggal)
 *   3. KETERANGAN LAPORAN (rekap otomatis di bawah tabel)
 *      - Jumlah Hadir/Izin/Sakit/Udzur/Terlambat/Alfa
 *      - Total Absensi
 *      - Total Poin
 *
 * Laporan TIDAK mengubah tabel absensi menjadi rekap per santri.
 */
class ReportService
{
    /**
     * Baris tabel laporan untuk aktivitas absensi.
     *
     * @return array{rows: array, keterangan: array}
     */
    public function activity(string $type, string $mulai, string $akhir, ?string $cari = null, ?int $santriId = null): array
    {
        $cfg = config("simpas.activities.{$type}");
        abort_unless($cfg, 404);

        $query = $cfg['model']::with('santri')
            ->whereBetween('tanggal', [$mulai, $akhir])
            ->cariSantri($cari);

        if ($santriId !== null) {
            $query->where('santri_id', $santriId);
        }

        $records = $query
            ->orderBy('tanggal')
            ->orderBy('santri_id')
            ->orderBy('sesi')
            ->get();

        // Kelompokkan: 1 baris = 1 santri + 1 tanggal (kolom = sesi)
        $groups = [];
        foreach ($records as $rec) {
            $key = $rec->santri_id . '|' . $rec->tanggal->format('Y-m-d');
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'santri' => $rec->santri,
                    'tanggal' => $rec->tanggal,
                    'sesi' => [],
                    'total' => 0,
                ];
            }
            $groups[$key]['sesi'][$rec->sesi] = $rec->status;
            $groups[$key]['total'] += (int) $rec->poin;
        }
        ksort($groups);

        $rows = array_values($groups);

        // Rekap otomatis (Keterangan Laporan)
        $statusCounts = array_fill_keys($cfg['statuses'], 0);
        foreach ($records as $rec) {
            $statusCounts[$rec->status] = ($statusCounts[$rec->status] ?? 0) + 1;
        }

        $keterangan = [
            'label' => $cfg['label'],
            'sessions' => $cfg['sessions'],
            'status' => $statusCounts,
            'total_absensi' => $records->count(),
            'total_poin' => (int) $records->sum('poin'),
            'jumlah_baris' => count($rows),
        ];

        return ['rows' => $rows, 'keterangan' => $keterangan];
    }

    /** Baris tabel + keterangan untuk Laporan Prestasi (bagian 33). */
    public function prestasi(string $mulai, string $akhir, ?string $cari = null, ?int $santriId = null): array
    {
        $query = Prestasi::with('santri')
            ->whereBetween('tanggal', [$mulai, $akhir])
            ->cariSantri($cari);

        if ($santriId !== null) {
            $query->where('santri_id', $santriId);
        }

        $items = $query->orderBy('tanggal')->get();

        $perJenis = [];
        foreach ($items as $item) {
            $perJenis[$item->jenis] = ($perJenis[$item->jenis] ?? 0) + 1;
        }

        return [
            'rows' => $items,
            'keterangan' => [
                'label' => 'Prestasi',
                'per_jenis' => $perJenis,
                'total_absensi' => $items->count(),
                'total_poin' => (int) $items->sum('poin'),
                'jumlah_baris' => $items->count(),
            ],
        ];
    }

    /** Baris tabel + keterangan untuk Laporan Pelanggaran (bagian 34). */
    public function pelanggaran(string $mulai, string $akhir, ?string $cari = null, ?int $santriId = null): array
    {
        $query = Pelanggaran::with('santri')
            ->whereBetween('tanggal', [$mulai, $akhir])
            ->cariSantri($cari);

        if ($santriId !== null) {
            $query->where('santri_id', $santriId);
        }

        $items = $query->orderBy('tanggal')->get();

        $perTingkat = ['Ringan' => 0, 'Sedang' => 0, 'Berat' => 0];
        foreach ($items as $item) {
            $perTingkat[$item->tingkat] = ($perTingkat[$item->tingkat] ?? 0) + 1;
        }

        return [
            'rows' => $items,
            'keterangan' => [
                'label' => 'Pelanggaran',
                'per_tingkat' => $perTingkat,
                'total_absensi' => $items->count(),
                'total_poin' => (int) $items->sum('poin'),
                'jumlah_baris' => $items->count(),
            ],
        ];
    }

    /** Router: ambil data laporan sesuai jenis. */
    public function build(string $jenis, string $mulai, string $akhir, ?string $cari = null, ?int $santriId = null): array
    {
        if ($jenis === 'prestasi') {
            return $this->prestasi($mulai, $akhir, $cari, $santriId);
        }

        if ($jenis === 'pelanggaran') {
            return $this->pelanggaran($mulai, $akhir, $cari, $santriId);
        }

        return $this->activity($jenis, $mulai, $akhir, $cari, $santriId);
    }
}
