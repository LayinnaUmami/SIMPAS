<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Menentukan rentang tanggal dari filter periode.
 *
 * Periode: hari | minggu | bulan | tahun | custom (bagian 24, 27)
 */
class PeriodResolver
{
    public const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * @return array{mulai: Carbon, akhir: Carbon, label: string, jenis: string}
     */
    public static function resolve(?string $periode = null, array $input = []): array
    {
        $periode = $periode ?: 'bulan';
        $now = Carbon::now();

        switch ($periode) {
            case 'hari':
                $tanggal = !empty($input['tanggal']) ? Carbon::parse($input['tanggal']) : $now->copy();
                $mulai = $tanggal->copy()->startOfDay();
                $akhir = $tanggal->copy()->endOfDay();
                $label = 'Periode Harian: ' . $mulai->translatedFormat('d F Y');
                break;

            case 'minggu':
                $tahun = (int) ($input['tahun'] ?? $now->year);
                $minggu = (int) ($input['minggu'] ?? $now->isoWeek);

                // Kita pakai setISODate() di bawah, jadi batas atas harus minggu ISO.
                // (->week bersifat locale-based dan salah untuk sebagian besar tanggal Desember.)
                $maxWeek = Carbon::create($tahun, 12, 28)->isoWeek;
                $minggu = min(max($minggu, 1), $maxWeek);

                $mulai = Carbon::now()->setISODate($tahun, $minggu, 1)->startOfDay();
                $akhir = $mulai->copy()->addDays(6)->endOfDay();
                $label = "Periode Mingguan: Minggu ke-{$minggu} / {$tahun} (" .
                    $mulai->translatedFormat('d F Y') . ' - ' . $akhir->translatedFormat('d F Y') . ')';
                break;

            case 'tahun':
                $tahun = (int) ($input['tahun'] ?? $now->year);
                $mulai = Carbon::create($tahun, 1, 1)->startOfDay();
                $akhir = Carbon::create($tahun, 12, 31)->endOfDay();
                $label = "Periode Tahunan: 1 Januari {$tahun} - 31 Desember {$tahun}";
                break;

            case 'custom':
                $mulai = !empty($input['mulai']) ? Carbon::parse($input['mulai']) : $now->copy()->startOfMonth();
                $akhir = !empty($input['akhir']) ? Carbon::parse($input['akhir']) : $now->copy()->endOfMonth();
                if ($akhir->lt($mulai)) {
                    [$mulai, $akhir] = [$akhir, $mulai];
                }
                $mulai = $mulai->copy()->startOfDay();
                $akhir = $akhir->copy()->endOfDay();
                $label = 'Periode Custom: ' . $mulai->translatedFormat('d F Y') . ' - ' . $akhir->translatedFormat('d F Y');
                break;

            case 'semua':
            case 'all':
                $periode = 'semua';
                $mulai = Carbon::create(1900, 1, 1)->startOfDay();
                $akhir = $now->copy()->endOfDay();
                $label = 'Seluruh Periode: ' . $mulai->translatedFormat('d F Y') . ' - ' . $akhir->translatedFormat('d F Y');
                break;

            case 'bulan':
            default:
                $periode = 'bulan';
                $bulan = (int) ($input['bulan'] ?? $now->month);
                $tahun = (int) ($input['tahun'] ?? $now->year);
                $bulan = min(max($bulan, 1), 12);
                $mulai = Carbon::create($tahun, $bulan, 1)->startOfDay();
                $akhir = $mulai->copy()->endOfMonth()->endOfDay();
                $label = 'Periode Bulanan: ' . $mulai->translatedFormat('d F Y') . ' - ' . $akhir->translatedFormat('d F Y');
                break;
        }

        return [
            'mulai' => $mulai,
            'akhir' => $akhir,
            'label' => $label,
            'jenis' => $periode,
        ];
    }

    /** Minggu ISO yang berisi sebuah tanggal, dipakai otomatisasi pelanggaran. */
    public static function weekKey(Carbon $date): string
    {
        return $date->format('Y') . '-W' . sprintf('%02d', $date->isoWeek);
    }

    /** Awal minggu (Senin) yang berisi tanggal. */
    public static function weekStart(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek();
    }

    public static function monthKey(Carbon $date): string
    {
        return $date->format('Y-m');
    }
}
