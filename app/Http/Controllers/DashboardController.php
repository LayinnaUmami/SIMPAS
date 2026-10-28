<?php

namespace App\Http\Controllers;

use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Services\PeriodResolver;
use App\Services\RankingService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(RankingService $rankingService)
    {
        $user = auth()->user();

        /*
         * Wali DAN Santri memakai dashboard khusus, supaya tidak melihat rekap
         * seluruh pondok. Wali hanya melihat anak yang terhubung melalui wali_id;
         * Santri hanya melihat profil yang dirujuk users.santri_id.
         *
         * Dulu hanya isWali() yang dialihkan. Akibatnya akun berperan "santri"
         * (ada 3 di basis data) jatuh ke dashboard admin dan bisa membaca total
         * Santri, aktivitas hari ini, serta ranking seluruh pondok.
         */
        if ($user?->isWaliOrSantri()) {
            return redirect()->route('wali.dashboard');
        }

        $today = Carbon::today();

        // Statistik umum (bagian 4)
        $totalSantri = Santri::aktif()->count();
        $totalAktivitasHariIni = 0;

        foreach (config('simpas.activities') as $cfg) {
            $totalAktivitasHariIni += $cfg['model']::where('tanggal', $today)->count();
        }

        $bulanIni = PeriodResolver::resolve('bulan');

        $totalPrestasi = Prestasi::whereBetween('tanggal', [
            $bulanIni['mulai']->format('Y-m-d'),
            $bulanIni['akhir']->format('Y-m-d'),
        ])->count();

        $totalPelanggaran = Pelanggaran::whereBetween('tanggal', [
            $bulanIni['mulai']->format('Y-m-d'),
            $bulanIni['akhir']->format('Y-m-d'),
        ])->count();

        // Ranking default: bulan berjalan (bagian 4)
        $ranking = $rankingService->ranking($bulanIni);

        $top = array_slice($ranking, 0, 5);

        // Poin sendiri (untuk role santri)
        $poinSaya = null;
        $rankSaya = null;
        if ($user->isSantri() && $user->santri_id) {
            foreach ($ranking as $row) {
                if ($row['santri']->id === $user->santri_id) {
                    $rankSaya = $row['rank'];
                    $poinSaya = $row['total'];
                    break;
                }
            }
        }

        // Ringkasan aktivitas hari ini per jenis
        $aktivitasHariIni = [];
        foreach (config('simpas.activities') as $key => $cfg) {
            $records = $cfg['model']::where('tanggal', $today)->get(['status', 'poin']);
            $aktivitasHariIni[$key] = [
                'label' => $cfg['label'],
                'icon' => $cfg['icon'],
                'total' => $records->count(),
                'hadir' => $records->where('status', 'Hadir')->count(),
                'alfa' => $records->where('status', 'Alfa')->count(),
                'poin' => (int) $records->sum('poin'),
            ];
        }

        $bulanLabel = PeriodResolver::BULAN[$bulanIni['mulai']->month] . ' ' . $bulanIni['mulai']->year;

        return view('dashboard', [
            'user' => $user,
            'totalSantri' => $totalSantri,
            'totalAktivitasHariIni' => $totalAktivitasHariIni,
            'totalPrestasi' => $totalPrestasi,
            'totalPelanggaran' => $totalPelanggaran,
            'ranking' => $top,
            'rankingLabel' => "Ranking {$bulanLabel}",
            'jumlahSantri' => count($ranking),
            'poinSaya' => $poinSaya,
            'rankSaya' => $rankSaya,
            'aktivitasHariIni' => $aktivitasHariIni,
        ]);
    }
}
