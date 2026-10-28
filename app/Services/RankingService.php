<?php

namespace App\Services;

use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use Carbon\Carbon;

/**
 * RANKING (bagian 23)
 *
 *   Total Ranking = Poin Aktivitas + Poin Prestasi + Poin Pelanggaran
 *
 * Diniyah TIDAK dihitung. Data dalam Sampah (soft delete) ikut terkecuali
 * secara otomatis karena global scope SoftDeletes.
 */
class RankingService
{
    /**
     * @param  array{mulai?: Carbon|null, akhir?: Carbon|null, jenis?: string}  $periode
     * @param  array  $filter  {nama?: string, jenis_kelamin?: string, kamar?: string}
     * @param  array<int, int>|null  $santriIds  Batasi kandidat ke daftar Santri tertentu.
     * @return array<int, array> daftar ranking terurut dari poin tertinggi
     */
    public function ranking(array $periode, array $filter = [], ?array $santriIds = null): array
    {
        $semua = ($periode['jenis'] ?? null) === 'semua'
            || empty($periode['mulai'])
            || empty($periode['akhir']);
        $mulai = $semua ? null : $periode['mulai']->format('Y-m-d');
        $akhir = $semua ? null : $periode['akhir']->format('Y-m-d');

        // --- Santri kandidat (aktif + filter) -------------------------------
        $santriQuery = Santri::aktif()->cari($filter['nama'] ?? null);

        if ($santriIds !== null) {
            $santriQuery->whereIn('id', $santriIds);
        }

        if (!empty($filter['jenis_kelamin'])) {
            $santriQuery->where('jenis_kelamin', $filter['jenis_kelamin']);
        }
        if (!empty($filter['kamar'])) {
            $santriQuery->where('kamar', $filter['kamar']);
        }

        $santris = $santriQuery->orderBy('nis')->get();

        if ($santris->isEmpty()) {
            return [];
        }

        $ids = $santris->modelKeys();

        // --- Poin aktivitas (semua jenis kecuali Diniyah) -------------------
        $aktivitas = [];
        foreach (config('simpas.activities') as $key => $cfg) {
            if (empty($cfg['weight'])) {
                continue; // Diniyah tidak ikut
            }

            $query = $cfg['model']::whereIn('santri_id', $ids);
            if (!$semua) {
                $query->whereBetween('tanggal', [$mulai, $akhir]);
            }

            $sums = $query
                ->groupBy('santri_id')
                ->selectRaw('santri_id, SUM(poin) as total')
                ->pluck('total', 'santri_id');

            foreach ($sums as $santriId => $total) {
                $aktivitas[$santriId] = ($aktivitas[$santriId] ?? 0) + (int) $total;
            }
        }

        // --- Poin prestasi ---------------------------------------------------
        $query = Prestasi::whereIn('santri_id', $ids);
        if (!$semua) {
            $query->whereBetween('tanggal', [$mulai, $akhir]);
        }

        $prestasi = $query
            ->groupBy('santri_id')
            ->selectRaw('santri_id, SUM(poin) as total')
            ->pluck('total', 'santri_id');

        // --- Poin pelanggaran (sudah negatif) --------------------------------
        $query = Pelanggaran::whereIn('santri_id', $ids);
        if (!$semua) {
            $query->whereBetween('tanggal', [$mulai, $akhir]);
        }

        $pelanggaran = $query
            ->groupBy('santri_id')
            ->selectRaw('santri_id, SUM(poin) as total')
            ->pluck('total', 'santri_id');

        // --- Susun ------------------------------------------------------------
        $rows = [];
        foreach ($santris as $santri) {
            $id = $santri->id;
            $a = (int) ($aktivitas[$id] ?? 0);
            $p = (int) ($prestasi[$id] ?? 0);
            $v = (int) ($pelanggaran[$id] ?? 0);

            $rows[] = [
                'santri' => $santri,
                'poin_aktivitas' => $a,
                'poin_prestasi' => $p,
                'poin_pelanggaran' => $v,
                'total' => $a + $p + $v,
            ];
        }

        // Urutkan: total desc, lalu nama asc
        usort($rows, function ($x, $y) {
            if ($x['total'] === $y['total']) {
                return strcmp($x['santri']->nama, $y['santri']->nama);
            }

            return $y['total'] <=> $x['total'];
        });

        // Nomor urut tetap berdasarkan posisi asli (pencarian hanya memfilter
        // tampilan - bagian 25)
        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
        }
        unset($row);

        // Filter tampilan (nama/NIS) TANPA mengubah nomor rank
        $cari = trim((string) ($filter['nama'] ?? ''));
        if ($cari !== '') {
            $rows = array_values(array_filter($rows, function ($row) use ($cari) {
                $nama = mb_strtolower($row['santri']->nama);
                $nis = mb_strtolower($row['santri']->nis);

                return str_contains($nama, mb_strtolower($cari)) || str_contains($nis, mb_strtolower($cari));
            }));
        }

        return $rows;
    }

    /** Ranking bulan berjalan - default dashboard (bagian 4). */
    public function bulanIni(array $filter = []): array
    {
        $now = Carbon::now();

        return $this->ranking([
            'mulai' => $now->copy()->startOfMonth(),
            'akhir' => $now->copy()->endOfMonth(),
        ], $filter);
    }
}
