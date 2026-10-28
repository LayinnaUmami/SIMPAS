<?php

namespace App\Services;

use App\Models\Santri;
use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan absensi massal dengan aturan anti-duplikasi.
 *
 * ATURAN (bagian 17):
 *   1 santri + 1 tanggal + 1 sesi = 1 data.
 *   Jika admin input ulang -> UPDATE data lama (bukan INSERT baru).
 *   Poin selalu di-*set* ulang sesuai status terbaru, tidak ditambahkan.
 */
class AttendanceService
{
    /**
     * @param  string  $type   key aktivitas (jamaah, pengajian, ...)
     * @param  string  $tanggal  tanggal Y-m-d
     * @param  array   $rows   [ ['santri_id'=>1, 'sesi'=>'Subuh', 'status'=>'Hadir'], ... ]
     * @return array{created:int, updated:int, pelanggaran:int}
     */
    public function save(string $type, string $tanggal, array $rows): array
    {
        $cfg = config("simpas.activities.{$type}");
        abort_unless($cfg, 404, 'Aktivitas tidak dikenal.');

        $modelClass = $cfg['model'];
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($modelClass, $tanggal, $rows, &$created, &$updated) {
            foreach ($rows as $row) {
                $santriId = (int) ($row['santri_id'] ?? 0);
                $sesi = (string) ($row['sesi'] ?? '');
                $status = (string) ($row['status'] ?? '');

                if (!$santriId || $sesi === '' || $status === '') {
                    continue;
                }

                // Cari data lama TERMASUK yang sudah di-soft delete
                $existing = $modelClass::withTrashed()
                    ->where('santri_id', $santriId)
                    ->where('tanggal', $tanggal)
                    ->where('sesi', $sesi)
                    ->first();

                $poin = $modelClass::pointsFor($status);

                if ($existing) {
                    // Pulihkan jika sebelumnya masuk Sampah, lalu perbarui
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->status = $status;
                    $existing->poin = $poin;      // SET, bukan tambah
                    $existing->save();
                    $updated++;
                } else {
                    $modelClass::create([
                        'santri_id' => $santriId,
                        'tanggal' => $tanggal,
                        'sesi' => $sesi,
                        'status' => $status,
                        'poin' => $poin,
                    ]);
                    $created++;
                }
            }
        });

        // Otomatisasi pelanggaran (bagian 20) - Diniyah dikecualikan
        $pelanggaran = (new AutoViolationService)->check($type, $tanggal);

        return ['created' => $created, 'updated' => $updated, 'pelanggaran' => $pelanggaran];
    }

    /**
     * Ambil data absensi untuk ditampilkan pada form input.
     * Data lama (punya record) dipakai sebagai nilai awal.
     *
     * @return array<int, array<string, string>>  [santri_id => [sesi => status]]
     */
    public function currentMap(string $type, string $tanggal): array
    {
        $cfg = config("simpas.activities.{$type}");

        $query = $cfg['model']::where('tanggal', $tanggal);

        // Untuk Diniyah: filter santri dengan jenis_diniyah = 'Diniyah'
        if ($type === 'diniyah') {
            $query->whereHas('santri', function ($q) {
                $q->where('jenis_diniyah', 'Diniyah');
            });
        }

        $records = $query->get();

        $map = [];
        foreach ($records as $rec) {
            $map[$rec->santri_id][$rec->sesi] = $rec->status;
        }

        return $map;
    }

    /**
     * Santri aktif untuk aktivitas tertentu.
     * Untuk Diniyah: hanya santri dengan jenis_diniyah = 'Diniyah' (bukan Mutimin/Asatidzah)
     */
    public function santriAktif(string $type = null)
    {
        $query = Santri::aktif()->orderBy('nis');

        if ($type === 'diniyah') {
            // Hanya santri dengan jenis_diniyah = 'Diniyah' yang punya absensi Diniyah
            // Mutimin dan Asatidzah TIDAK punya absensi Diniyah
            $query->where('jenis_diniyah', 'Diniyah');
        }

        return $query->get();
    }
}
