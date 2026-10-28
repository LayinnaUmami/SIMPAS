<?php

namespace App\Http\Controllers;

use App\Services\PeriodResolver;
use App\Services\RankingService;
use Illuminate\Http\Request;

/**
 * RANKING (bagian 23 - 25)
 */
class RankingController extends Controller
{
    public function index(Request $request, RankingService $service)
    {
        /*
         * Wali DAN Santri memakai halaman ranking sendiri. Halaman /wali
         * menampilkan ranking pondok dengan baris "Anak Anda" diberi tanda,
         * sehingga tidak membocorkan data pribadi Santri lain.
         *
         * Dulu hanya isWali() yang dialihkan. Akibatnya akun berperan "santri"
         * (ada 3 di basis data) melihat ranking lengkap seluruh pondok: nama,
         * NIS, kamar, dan poin setiap Santri.
         */
        if (auth()->user()?->isWaliOrSantri()) {
            return redirect()->route('wali.ranking', $request->query());
        }

        $periode = PeriodResolver::resolve($request->periode, $request->all());

        $filter = [
            'nama' => trim((string) $request->q),
            'jenis_kelamin' => $request->jenis_kelamin,
            'kamar' => $request->kamar,
        ];

        $rows = $service->ranking($periode, $filter);

        $now = now();

        return view('ranking.index', [
            'rows' => $rows,
            'periode' => $periode,
            'filter' => $filter,
            'input' => $request->all(),
            'kamar' => \App\Models\Santri::aktif()->whereNotNull('kamar')->distinct()->pluck('kamar')->sort()->values(),
            'tahun' => $now->year,
            'minggu' => (int) $request->input('minggu', $now->isoWeek),
            'bulan' => (int) $request->input('bulan', $now->month),
            'bulanNama' => fn ($m) => PeriodResolver::BULAN[$m] ?? '-',
        ]);
    }
}
