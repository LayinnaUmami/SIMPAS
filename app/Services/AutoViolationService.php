<?php

namespace App\Services;

use App\Models\Pelanggaran;
use App\Models\Santri;
use Carbon\Carbon;

/**
 * OTOMATISASI PELANGGARAN (bagian 20)
 *
 * Jamaah    : 3 Alfa dalam 1 minggu
 * Pengajian : 3 Alfa dalam 1 bulan
 * Deresan   : 3 Alfa dalam 1 bulan
 * Sorogan   : 3 Alfa dalam 1 bulan
 * Murojaah  : 3 Alfa dalam 1 bulan
 * Ratiban   : 3 Alfa dalam 1 bulan
 * Diniyah   : TIDAK ada otomatisasi
 *
 * Anti-duplikasi: pelanggaran otomatis hanya dibuat SEKALI per
 * santri + periode + jenis (dicegah lewat kolom auto_key yang unik).
 */
class AutoViolationService
{
    /**
     * @return int jumlah pelanggaran otomatis yang baru dibuat
     */
    public function check(string $type, string $tanggal): int
    {
        $cfg = config("simpas.activities.{$type}");

        if (!config('simpas.auto_violation.enabled', true)) {
            return 0;
        }

        // Diniyah tidak menggunakan otomatisasi pelanggaran
        if (empty($cfg['violation'])) {
            return 0;
        }

        $rule = $cfg['violation'];
        $date = Carbon::parse($tanggal);

        // Periode tetap: mingguan (Senin-Minggu) atau bulanan
        $mingguan = $rule['days'] <= 7;

        if ($mingguan) {
            $mulai = PeriodResolver::weekStart($date)->format('Y-m-d');
            $akhir = Carbon::parse($mulai)->addDays(6)->format('Y-m-d');
            $periodKey = PeriodResolver::weekKey($date);
        } else {
            $mulai = $date->copy()->startOfMonth()->format('Y-m-d');
            $akhir = $date->copy()->endOfMonth()->format('Y-m-d');
            $periodKey = PeriodResolver::monthKey($date);
        }

        $modelClass = $cfg['model'];
        $created = 0;

        // Hitung jumlah Alfa per santri pada periode tersebut
        $alfa = $modelClass::where('status', 'Alfa')
            ->whereBetween('tanggal', [$mulai, $akhir])
            ->groupBy('santri_id')
            ->selectRaw('santri_id, COUNT(*) as jml')
            ->pluck('jml', 'santri_id');

        foreach ($alfa as $santriId => $jml) {
            if ((int) $jml < (int) $rule['threshold']) {
                continue;
            }

            $autoKey = "{$type}|{$santriId}|{$periodKey}";

            // Sudah pernah dibuat? -> Jangan buat lagi
            if (Pelanggaran::withTrashed()->where('auto_key', $autoKey)->exists()) {
                continue;
            }

            $santri = Santri::find($santriId);
            if (!$santri) {
                continue;
            }

            $rentang = Carbon::parse($mulai)->translatedFormat('d M Y')
                . ' - ' . Carbon::parse($akhir)->translatedFormat('d M Y');

            Pelanggaran::create([
                'santri_id' => $santriId,
                'tanggal' => $akhir,
                'jenis' => $rule['jenis'],
                'tingkat' => config('simpas.auto_violation.tingkat', 'Sedang'),
                'keterangan' => "Otomatis: {$rule['label']} sebanyak {$jml}x pada periode {$rentang}",
                'poin' => (int) config('simpas.auto_violation.poin', -5),
                'is_auto' => true,
                'auto_key' => $autoKey,
            ]);

            $created++;
        }

        return $created;
    }
}
