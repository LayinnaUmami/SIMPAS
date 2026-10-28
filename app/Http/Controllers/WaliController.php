<?php

namespace App\Http\Controllers;

use App\Models\Khataman;
use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Services\PeriodResolver;
use App\Services\RankingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

/**
 * Halaman untuk Wali Santri.
 *
 * Semua query data pada controller ini diambil dari Santri yang terhubung
 * dengan akun wali. Filter hanya mempersempit periode data anak tersebut;
 * wali tidak dapat memilih atau melihat Santri lain.
 */
class WaliController extends Controller
{
    /** @var array<int, string> */
    protected $middleware = ['wali'];

    public function dashboard(Request $request, RankingService $rankingService)
    {
        $santris = $this->waliSantris($request);
        $santri = $this->waliSantri($request);
        $today = Carbon::today();

        $aktivitasHariIni = [];
        foreach (config('simpas.activities') as $key => $cfg) {
            // Diniyah hanya berlaku untuk Santri dengan jenis Diniyah.
            if ($key === 'diniyah' && $santri->jenis_diniyah !== 'Diniyah') {
                continue;
            }

            $records = $cfg['model']::query()
                ->where('santri_id', $santri->id)
                ->whereDate('tanggal', $today)
                ->get(['status', 'poin']);

            $aktivitasHariIni[$key] = [
                'label' => $cfg['label'],
                'icon' => $cfg['icon'],
                'color' => $cfg['color'],
                'total' => $records->count(),
                'hadir' => $records->where('status', 'Hadir')->count(),
                'alfa' => $records->where('status', 'Alfa')->count(),
                'poin' => (int) $records->sum('poin'),
            ];
        }

        $bulanIni = PeriodResolver::resolve('bulan');
        $rankingSaya = collect($rankingService->ranking($bulanIni))
            ->first(fn (array $row) => (int) $row['santri']->id === (int) $santri->id);

        $poinAktivitasBulanIni = 0;
        foreach (config('simpas.activities') as $key => $cfg) {
            if (empty($cfg['weight'])) {
                continue;
            }

            $poinAktivitasBulanIni += (int) $cfg['model']::query()
                ->where('santri_id', $santri->id)
                ->whereBetween('tanggal', [
                    $bulanIni['mulai']->format('Y-m-d'),
                    $bulanIni['akhir']->format('Y-m-d'),
                ])
                ->sum('poin');
        }

        $totalPrestasi = Prestasi::query()
            ->where('santri_id', $santri->id)
            ->whereBetween('tanggal', [
                $bulanIni['mulai']->format('Y-m-d'),
                $bulanIni['akhir']->format('Y-m-d'),
            ])
            ->sum('poin');
        $totalPelanggaran = Pelanggaran::query()
            ->where('santri_id', $santri->id)
            ->whereBetween('tanggal', [
                $bulanIni['mulai']->format('Y-m-d'),
                $bulanIni['akhir']->format('Y-m-d'),
            ])
            ->sum('poin');

        return view('wali.dashboard', [
            'user' => $request->user(),
            'santri' => $santri,
            'santris' => $santris,
            'aktivitasHariIni' => $aktivitasHariIni,
            'rankSaya' => $rankingSaya['rank'] ?? null,
            'poinSaya' => $rankingSaya['total'] ?? 0,
            'totalPrestasi' => $totalPrestasi,
            'totalPelanggaran' => $totalPelanggaran,
            'poinAktivitasBulanIni' => $poinAktivitasBulanIni,
            'rankingSaya' => $rankingSaya,
        ]);
    }

    /**
     * Riwayat absensi tunggal. Halaman ini tidak ditampilkan di menu Wali,
     * tetapi tetap dipertahankan dan selalu dibatasi ke anak yang terhubung.
     */
    public function aktivitas(Request $request)
    {
        $santri = $this->waliSantri($request);
        $activities = config('simpas.activities');
        if ($santri->jenis_diniyah !== 'Diniyah') {
            unset($activities['diniyah']);
        }

        $type = (string) $request->query('type', array_key_first($activities));

        abort_unless(array_key_exists($type, $activities), 404, 'Jenis aktivitas tidak dikenal.');

        if ($type === 'diniyah' && $santri->jenis_diniyah !== 'Diniyah') {
            abort(404, 'Absensi Diniyah tidak tersedia untuk Santri ini.');
        }

        $tanggal = $this->tanggal($request->query('tanggal'));
        $cfg = $activities[$type];

        $records = $cfg['model']::with('santri')
            ->where('santri_id', $santri->id)
            ->whereDate('tanggal', $tanggal)
            ->orderBy('sesi')
            ->get();

        // Satu baris per tanggal; setiap sesi menjadi kolom status.
        $rows = $records->groupBy(fn ($record) => $record->tanggal->format('Y-m-d'))
            ->map(function ($group) {
                $first = $group->first();

                return (object) [
                    'santri' => $first->santri,
                    'tanggal' => $first->tanggal,
                    'sesi' => $group->pluck('status', 'sesi')->all(),
                    'total' => (int) $group->sum('poin'),
                ];
            })
            ->values();

        return view('wali.aktivitas', [
            'santri' => $santri,
            'type' => $type,
            'tanggal' => $tanggal,
            'records' => $rows,
            'types' => array_keys($activities),
            'cfg' => $cfg,
        ]);
    }

    /** Prestasi anak: hanya filter Semua/Tahun. */
    public function prestasi(Request $request)
    {
        $santri = $this->waliSantri($request);
        $requestedPeriode = $request->query('periode');
        $periode = $requestedPeriode === 'all' ? 'semua' : $requestedPeriode;
        $periode = in_array($periode, ['semua', 'tahun'], true)
            ? (string) $periode
            : 'semua';
        $tahun = $this->tahun($request->query('tahun'));

        $query = Prestasi::with('santri')
            ->where('santri_id', $santri->id);

        if ($periode === 'tahun') {
            $query->whereYear('tanggal', $tahun);
        }

        $prestasi = $query->orderByDesc('tanggal')->paginate(10)->withQueryString();

        // Tahun yang benar-benar punya data diambil terpisah dari kueri utama
        // supaya tidak ikut dipaginasi.
        $tahunDariData = Prestasi::query()
            ->where('santri_id', $santri->id)
            ->get(['tanggal'])
            ->map(fn ($item) => (int) $item->tanggal->format('Y'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        /*
         * Tahun yang punya data, ditambah tahun berjalan agar pilihan tidak
         * kosong ketika sedang memasukkan data.
         *
         * Catatan penting: get() menghasilkan Eloquent Collection, dan map()
         * di atas mengubah isinya menjadi bilangan bulat biasa. Eloquent
         * Collection mengira isinya masih model, sehingga unique() memanggil
         * getKey() pada sebuah int dan aplikasi gagal dengan
         * "Call to a member function getKey() on int".
         *
         * toBase() mengubah kumpulan ini menjadi Collection biasa yang
         * bekerja pada nilai biasa, sehingga unique() dan sortDesc() aman.
         */
        $tahunOptions = $tahunDariData
            ->toBase()
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('wali.prestasi', compact('santri', 'prestasi', 'periode', 'tahun', 'tahunOptions'));
    }

    /** Pelanggaran anak: filter periode lengkap, tanpa pencarian/select Santri. */
    public function pelanggaran(Request $request)
    {
        $santri = $this->waliSantri($request);
        $periode = $this->periodeValid(
            $request->query('periode', 'semua'),
            ['semua', 'hari', 'minggu', 'bulan', 'tahun', 'custom']
        );
        $periodeData = $this->periodeData($periode, $request);
        $filter = $this->filterNilai($request);

        $query = Pelanggaran::with('santri')
            ->where('santri_id', $santri->id);

        if ($periode !== 'semua') {
            $query->whereBetween('tanggal', [
                $periodeData['mulai']->format('Y-m-d'),
                $periodeData['akhir']->format('Y-m-d'),
            ]);
        }

        $pelanggaran = $query->orderByDesc('tanggal')->paginate(10)->withQueryString();

        return view('wali.pelanggaran', compact('santri', 'pelanggaran', 'periode', 'periodeData', 'filter'));
    }

    /** Riwayat khataman hanya milik anak yang terhubung. */
    public function khataman(Request $request)
    {
        $santri = $this->waliSantri($request);

        $khatamans = Khataman::query()
            ->where('santri_id', $santri->id)
            ->orderByDesc('tahun')
            ->get();

        return view('wali.khataman', compact('santri', 'khatamans'));
    }

    /**
     * Laporan wali. Jenis laporan adalah setiap activity, prestasi, atau
     * pelanggaran. Tidak ada parameter q/santri_id yang dipakai.
     */
    public function laporan(Request $request)
    {
        $santri = $this->waliSantri($request);
        $activities = config('simpas.activities');

        // Mutimin/Asatidzah tidak memiliki absensi Diniyah.
        if ($santri->jenis_diniyah !== 'Diniyah') {
            unset($activities['diniyah']);
        }

        $jenisList = [];
        foreach ($activities as $key => $cfg) {
            $jenisList[$key] = $cfg['label'];
        }
        $jenisList['prestasi'] = 'Prestasi';
        $jenisList['pelanggaran'] = 'Pelanggaran';

        $jenis = (string) $request->query('jenis', 'jamaah');
        abort_unless(array_key_exists($jenis, $jenisList), 404, 'Jenis laporan tidak dikenal.');

        $periode = $this->periodeValid(
            $request->query('periode', 'bulan'),
            ['bulan', 'hari', 'minggu', 'tahun', 'custom']
        );
        $periodeData = $this->periodeData($periode, $request);
        $filter = $this->filterNilai($request);
        $isActivity = array_key_exists($jenis, $activities);
        $rows = collect();
        $keterangan = [];

        if ($isActivity) {
            $cfg = $activities[$jenis];
            $query = $cfg['model']::with('santri')
                ->where('santri_id', $santri->id);

            if ($periode !== 'semua') {
                $query->whereBetween('tanggal', [
                    $periodeData['mulai']->format('Y-m-d'),
                    $periodeData['akhir']->format('Y-m-d'),
                ]);
            }

            $records = $query->orderBy('tanggal')->orderBy('sesi')->get();
            $rows = $records
                ->groupBy(fn ($record) => $record->tanggal->format('Y-m-d'))
                ->map(function ($group) {
                    $first = $group->first();
                    $sesi = [];

                    foreach ($group as $record) {
                        $sesi[$record->sesi] = $record->status;
                    }

                    return [
                        'santri' => $first->santri,
                        'tanggal' => $first->tanggal,
                        'sesi' => $sesi,
                        'total' => (int) $group->sum('poin'),
                    ];
                })
                ->values();

            $status = array_fill_keys(config('simpas.full_statuses', $cfg['statuses']), 0);
            foreach ($records as $record) {
                $status[$record->status] = ($status[$record->status] ?? 0) + 1;
            }

            $keterangan = [
                'status' => $status,
                'total_absensi' => $records->count(),
                'total_poin' => (int) $records->sum('poin'),
                'jumlah_baris' => $rows->count(),
            ];
        } elseif ($jenis === 'prestasi') {
            $query = Prestasi::with('santri')->where('santri_id', $santri->id);
            if ($periode !== 'semua') {
                $query->whereBetween('tanggal', [
                    $periodeData['mulai']->format('Y-m-d'),
                    $periodeData['akhir']->format('Y-m-d'),
                ]);
            }
            $rows = $query->orderByDesc('tanggal')->get();
            $keterangan = [
                'total_data' => $rows->count(),
                'total_poin' => (int) $rows->sum('poin'),
            ];
        } else {
            $query = Pelanggaran::with('santri')->where('santri_id', $santri->id);
            if ($periode !== 'semua') {
                $query->whereBetween('tanggal', [
                    $periodeData['mulai']->format('Y-m-d'),
                    $periodeData['akhir']->format('Y-m-d'),
                ]);
            }
            $rows = $query->orderByDesc('tanggal')->get();
            $keterangan = [
                'total_data' => $rows->count(),
                'total_poin' => (int) $rows->sum('poin'),
            ];
        }

        return view('wali.laporan', [
            'santri' => $santri,
            'jenis' => $jenis,
            'jenisList' => $jenisList,
            'periode' => $periodeData,
            'periodeJenis' => $periode,
            'filter' => $filter,
            'rows' => $rows,
            'keterangan' => $keterangan,
            'isActivity' => $isActivity,
            'cfg' => $isActivity ? $activities[$jenis] : null,
        ]);
    }

    /** Data Santri: hanya profil anak yang terhubung. */
    public function santri(Request $request)
    {
        $santri = $this->waliSantri($request)->load('khatamans');

        return view('santri.show', compact('santri'));
    }

    /** Ranking: menampilkan semua santri dengan highlight anak sendiri. */
    public function ranking(Request $request, RankingService $rankingService)
    {
        $santri = $this->waliSantri($request);
        $santriIds = $this->waliSantris($request)->modelKeys();
        $periode = $this->periodeValid(
            $request->query('periode', 'bulan'),
            ['bulan', 'minggu', 'tahun', 'semua', 'custom']
        );
        $periodeData = $this->periodeData($periode, $request);
        $filter = $this->filterNilai($request);

        // Ambil semua ranking, highlight anak sendiri
        $rows = $rankingService->ranking($periodeData, [], $santriIds);

        $anakIds = $santriIds;

        return view('wali.ranking', compact('santri', 'anakIds', 'rows', 'periode', 'periodeData', 'filter'));
    }

    public function profil(Request $request)
    {
        return view('wali.profile', [
            // 'santris', bukan 'santri': accessor anak utama bukan relasi
            // Eloquent, jadi tidak bisa di-load.
            'user' => $request->user()->load('santris'),
        ]);
    }

    public function updateProfil(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('users', 'public');
        }

        $request->user()->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'Password lama',
            'password' => 'Password baru',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    /**
     * Daftar anak yang boleh dipantau akun wali ini.
     *
     * Satu wali bisa punya beberapa anak, jadi pemanggilan ini mengembalikan
     * seluruhnya. Urutannya anak utama lebih dulu.
     */
    private function waliSantris(Request $request)
    {
        $user = $request->user();
        $daftar = $user->isSantri()
            ? Santri::query()->whereKey($user->santri_id)->with('khatamans')->get()
            : $user->santris()->with('khatamans')->get();

        abort_if($daftar->isEmpty(), 403, 'Akun wali belum terhubung dengan data Santri.');

        return $daftar;
    }

    /**
     * Santri yang sedang dipilih untuk ditampilkan.
     *
     * Anak yang dipilih disimpan di sesi, bukan diambil dari query string, karena
     * ia menentukan data yang tampil di SELURUH halaman wali. ID di URL tidak
     * pernah dipercaya begitu saja: anak yang dipilih harus benar-benar ada di
     * daftar anak milik akun ini, kalau tidak hasilnya 403.
     *
     * Bila belum ada pilihan, anak utama yang dipakai.
     */
    private function waliSantri(Request $request): Santri
    {
        $daftar = $this->waliSantris($request);

        $terpilih = $request->session()->get('wali_santri_terpilih');

        if ($terpilih) {
            $santri = $daftar->firstWhere('id', (int) $terpilih);

            // Id di sesi tidak lagi ada di daftar anak (misalnya anak dilepas
            // admin). Buang agar tidak terkunci pada data yang sudah tidak berhak.
            if ($santri) {
                return $santri;
            }

            $request->session()->forget('wali_santri_terpilih');
        }

        $anakUtama = $request->user()->anakUtama();

        abort_unless($anakUtama, 403, 'Akun wali belum terhubung dengan data Santri.');

        return $anakUtama->load('khatamans');
    }

    /**
     * Mengganti anak yang ditampilkan.
     *
     * POST, bukan GET, karena mengganti anak adalah perubahan keadaan dan tidak
     * boleh terjadi hanya karena sebuah tautan dibuka.
     */
    public function pilihSantri(Request $request)
    {
        $data = $request->validate([
            'santri_id' => ['required', 'integer'],
        ], [
            'santri_id.required' => 'Anak harus dipilih.',
            'santri_id.integer' => 'Data anak tidak valid.',
        ], [
            'santri_id' => 'anak',
        ]);

        $daftar = $this->waliSantris($request);

        // Pemeriksaan di sini yang benar-benar menentukan: ID dari form tetap
        // dicocokkan dengan daftar anak milik akun ini, bukan sekadar diambil.
        abort_if(! $daftar->contains('id', (int) $data['santri_id']), 403);

        $request->session()->put('wali_santri_terpilih', (int) $data['santri_id']);

        return redirect()->route('wali.dashboard')->with('toast', [
            'type' => 'success',
            'message' => 'Data anak berhasil diganti.',
        ]);
    }

    private function tanggal(mixed $value): Carbon
    {
        $value = $this->tanggalInput($value);

        return $value ? Carbon::parse($value)->startOfDay() : Carbon::today();
    }

    private function tanggalInput(mixed $value): string
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return '';
        }

        try {
            return Carbon::parse(trim((string) $value))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function tahun(mixed $value): int
    {
        $year = is_scalar($value) && $value !== '' ? (int) $value : (int) now()->year;

        return max(1950, min($year, 2100));
    }

    /** @param array<int, string> $allowed */
    private function periodeValid(mixed $value, array $allowed): string
    {
        if ($value === 'all' && in_array('semua', $allowed, true)) {
            return 'semua';
        }

        return in_array($value, $allowed, true) ? (string) $value : $allowed[0];
    }

    private function filterNilai(Request $request): array
    {
        return [
            'tanggal' => $this->tanggalInput($request->query('tanggal')) ?: now()->format('Y-m-d'),
            'minggu' => max(1, min(53, (int) $request->query('minggu', now()->isoWeek))),
            'bulan' => max(1, min(12, (int) $request->query('bulan', now()->month))),
            'tahun' => $this->tahun($request->query('tahun')),
            'mulai' => $this->tanggalInput($request->query('mulai')),
            'akhir' => $this->tanggalInput($request->query('akhir')),
        ];
    }

    private function periodeData(string $periode, Request $request): array
    {
        if ($periode === 'semua') {
            return [
                'mulai' => null,
                'akhir' => null,
                'label' => 'Seluruh Periode',
                'jenis' => 'semua',
            ];
        }

        return PeriodResolver::resolve($periode, $this->filterNilai($request));
    }
}
