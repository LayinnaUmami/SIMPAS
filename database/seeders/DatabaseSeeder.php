<?php

namespace Database\Seeders;

use App\Models\Khataman;
use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Models\User;
use App\Services\AutoViolationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /* ---------------------------------------------------------------- */
        /* AKUN                                                             */
        /* ---------------------------------------------------------------- */
        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Admin Pengurus',
            'password' => 'admin123',
            'role' => 'admin',
            'email' => 'admin@simpas.id',
            'telepon' => '081234567890',
        ]);

        /* ---------------------------------------------------------------- */
        /* DATA SANTRI (identitas saja - bagian 5 & 6)                       */
        /* ---------------------------------------------------------------- */
        $data = [
            ['240001', 'Ahmad Fauzan', 'Putra', 'Al-Hikmah', 'Pelajar', 'Diniyah', "1 Wustho"],
            ['240002', 'Muhammad Ali', 'Putra', 'Al-Falah', 'Pelajar', 'Diniyah', "2 Wustho"],
            ['240003', 'Siti Aisyah', 'Putri', 'Al-Hikmah Putri', 'Pelajar', 'Diniyah', "1 'Ulya"],
            ['240004', 'Abdurrahman Hakim', 'Putra', 'Al-Ikhlas', 'Mahasiswa', 'Diniyah', "3 Wustho"],
            ['240005', 'Zahra Nabila', 'Putri', 'An-Nur Putri', 'Pelajar', 'Diniyah', "2 'Ulya"],
            ['240006', 'Rizky Maulana', 'Putra', 'An-Nur', 'Pelajar', 'Asatidzah', null],
            ['240007', 'Fatimah Az-Zahra', 'Putri', 'Al-Falah Putri', 'S1', 'Diniyah', "1 Wustho"],
            ['240008', 'Hafidz Ramadhan', 'Putra', 'Al-Muttaqin', 'Takhasus', 'Mutimin', null],
            ['240009', 'Khadijah Nurul', 'Putri', 'An-Nur Putri', 'Pelajar', 'Diniyah', "3 'Ulya"],
            ['240010', 'Ibrahim Yusuf', 'Putra', 'Al-Hikmah', 'Pelajar', 'Diniyah', "2 Wustho"],
            ['240011', 'Aminatus Salimah', 'Putri', 'Al-Hikmah Putri', 'Pelajar', 'Diniyah', "1 'Ulya"],
            ['240012', 'Abdullah Karim', 'Putra', 'Al-Falah', 'Mahasiswa', 'Asatidzah', null],
        ];

        $santris = collect();

        foreach ($data as $i => [$nis, $nama, $jk, $kamar, $formal, $jenisDiniyah, $level]) {
            $santri = Santri::updateOrCreate(['nis' => $nis], [
                'nama' => $nama,
                'jenis_kelamin' => $jk,
                'alamat' => 'Jl. Pesantren No. ' . ($i + 1) . ', Kec. Simpang, Kab. Nganjuk',
                'kamar' => $kamar,
                'status_aktif' => 'Aktif',
                'pendidikan_formal' => $formal,
                'jenis_diniyah' => $jenisDiniyah,
                'level_diniyah' => $level,
                'nama_ayah' => 'Bapak ' . $nama,
                'pekerjaan_ayah' => ['Petani', 'Wiraswasta', 'Guru', 'Karyawan Swasta'][$i % 4],
                'nama_ibu' => 'Ibu ' . explode(' ', $nama)[0],
                'pekerjaan_ibu' => ['Ibu Rumah Tangga', 'Guru MI', 'Pedagang'][$i % 3],
                'nama_wali' => $i % 3 === 0 ? 'Bapak KH. Abdurrahman' : null,
                'pekerjaan_wali' => $i % 3 === 0 ? 'Pengasuh' : null,
                'no_hp' => '081298765' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'alamat_wali' => $i % 3 === 0 ? 'Jl. Wali No. 12' : null,
            ]);

            $santris->push($santri);

            // Akun santri/wali untuk 3 santri pertama
            if ($i < 3) {
                User::updateOrCreate(['username' => $nis], [
                    'name' => $nama,
                    'password' => 'santri123',
                    'role' => 'santri',
                    'santri_id' => $santri->id,
                ]);
            }
        }

        /* ---------------------------------------------------------------- */
        /* AKUN WALI DEMO (satu wali dengan lebih dari satu anak)             */
        /* ---------------------------------------------------------------- */
        $waliDemo = User::updateOrCreate(['username' => 'wali-demo'], [
            'name' => 'Wali Demo Multi Anak',
            'email' => 'wali-demo@simpas.id',
            'email_verified_at' => now(),
            'password' => Hash::make('wali12345'),
            'role' => User::ROLE_WALI,
            'is_active' => true,
        ]);

        foreach ([
            ['249901', 'Santri Demo Pertama', 'Putra'],
            ['249902', 'Santri Demo Kedua', 'Putri'],
        ] as [$nis, $nama, $jenisKelamin]) {
            $anakDemo = Santri::updateOrCreate(['nis' => $nis], [
                'nama' => $nama,
                'jenis_kelamin' => $jenisKelamin,
                'status_aktif' => 'Aktif',
                'alamat' => 'Data demo akun wali multi-anak',
            ]);

            $anakDemo->forceFill(['wali_id' => $waliDemo->id])->save();
        }

        $waliDemo->forceFill([
            'santri_id' => Santri::where('nis', '249901')->value('id'),
        ])->saveQuietly();

        foreach ($santris as $santri) {
            $username = 'wali' . str_pad((string) $santri->id, 3, '0', STR_PAD_LEFT);
            $wali = User::updateOrCreate(['username' => $username], [
                'name' => 'Wali ' . $santri->nama,
                'password' => Hash::make($username . '123'),
                'role' => User::ROLE_WALI,
                'santri_id' => $santri->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $santri->forceFill(['wali_id' => $wali->id])->save();
        }

        /* ---------------------------------------------------------------- */
        /* RIWAYAT KHATAMAN (bagian 7 - dapat berkali-kali)                  */
        /* ---------------------------------------------------------------- */
        Khataman::updateOrCreate(
            ['santri_id' => $santris[0]->id, 'jenis_khatam' => '30 Juz', 'tahun' => 2024],
            []
        );
        Khataman::updateOrCreate(
            ['santri_id' => $santris[0]->id, 'jenis_khatam' => '30 Juz', 'tahun' => 2025],
            []
        );
        Khataman::updateOrCreate(
            ['santri_id' => $santris[1]->id, 'jenis_khatam' => '20 Juz', 'tahun' => 2025],
            []
        );
        Khataman::updateOrCreate(
            ['santri_id' => $santris[2]->id, 'jenis_khatam' => '30 Juz', 'tahun' => 2026],
            []
        );

        /* ---------------------------------------------------------------- */
        /* ABSENSI 30 HARI TERAKHIR                                          */
        /* ---------------------------------------------------------------- */
        $points = config('simpas.points');

        $bobotPenuh = [
            'Hadir' => 78, 'Terlambat' => 6, 'Izin' => 7,
            'Sakit' => 3, 'Udzur' => 2, 'Alfa' => 3, 'Libur' => 1,
        ];
        $bobotJamaah = ['Hadir' => 86, 'Izin' => 9, 'Alfa' => 5];

        $today = Carbon::today();

        foreach (config('simpas.activities') as $key => $cfg) {
            $model = $cfg['model'];
            $table = $cfg['table'];

            for ($d = 29; $d >= 0; $d--) {
                $tanggal = $today->copy()->subDays($d);
                $dayName = $tanggal->format('l');

                // Murojaah: mingguan (Minggu), Ratiban: mingguan (Jumat) + hari ini
                if ($key === 'murojaah' && $dayName !== 'Sunday' && ! $tanggal->isToday()) {
                    continue;
                }
                if ($key === 'ratiban' && $dayName !== 'Friday' && ! $tanggal->isToday()) {
                    continue;
                }

                $bobot = $key === 'jamaah' ? $bobotJamaah : $bobotPenuh;

                foreach ($santris as $santri) {
                    foreach ($cfg['sessions'] as $sesi) {
                        $status = $this->acak($bobot);

                        // Status di luar daftar yang diizinkan -> sesuaikan
                        if (! in_array($status, $cfg['statuses'], true)) {
                            $status = $cfg['statuses'][0];
                        }

                        $model::updateOrCreate(
                            [
                                'santri_id' => $santri->id,
                                'tanggal' => $tanggal->toDateString(),
                                'sesi' => $sesi,
                            ],
                            [
                                'status' => $status,
                                'poin' => $points[$status] ?? 0,
                            ]
                        );
                    }
                }
            }
        }

        /* ---------------------------------------------------------------- */
        /* PRESTASI (bagian 21)                                              */
        /* ---------------------------------------------------------------- */
        $prestasi = [
            [$santris[0], 'Al-Qur\'an', 'Juara 1 Tahfidz 5 Juz', 'Kecamatan', 5, 'Lembaga Tahfidz', 'Selamat!', 10],
            [$santris[2], 'Akademik', 'Juara 2 Lomba Bahasa Arab', 'Kabupaten', 8, 'Dinas Pendidikan', '', 15],
            [$santris[1], 'Olahraga', 'Juara 1 Futsal Antar Kamar', 'Pondok', 12, 'Panitia Olahraga', '', 5],
            [$santris[4], 'Seni', 'Juara 2 Baca Kitab Kuning', 'Desa', 15, 'Panitia Maulid', '', 5],
            [$santris[6], 'Keagamaan', 'Juara 1 Adzan', 'Kecamatan', 18, 'Musholla Al-Ikhlas', '', 10],
            [$santris[3], 'Organisasi', 'Ketua OSIS Berprestasi', 'Pondok', 3, 'Pengurus Pondok', '', 8],
            [$santris[8], 'Akademik', 'Nilai Tertinggi Ujian', 'Pondok', 20, 'Panitia Ujian', '', 5],
            [$santris[0], "Al-Qur'an", 'Juara 1 Murattal', 'Provinsi', 24, 'Kemenag Provinsi', 'Prestasi membanggakan', 25],
        ];

        foreach ($prestasi as [$santri, $jenis, $nama, $tingkat, $lalu, $penyelenggara, $ket, $poin]) {
            Prestasi::updateOrCreate(
                [
                    'santri_id' => $santri->id,
                    'nama_prestasi' => $nama,
                    'tanggal' => $today->copy()->subDays($lalu)->toDateString(),
                ],
                [
                    'jenis' => $jenis,
                    'tingkat' => $tingkat,
                    'penyelenggara' => $penyelenggara,
                    'keterangan' => $ket,
                    'poin' => $poin,
                ]
            );
        }

        /* ---------------------------------------------------------------- */
        /* PELANGGARAN MANUAL (bagian 22)                                    */
        /* ---------------------------------------------------------------- */
        $pelanggaran = [
            [$santris[9], 'Terlambat', 'Ringan', 'Terlambat subuh 2 kali', 2, -3],
            [$santris[5], 'Melanggar tata tertib', 'Sedang', 'Membawa HP di luar jadwal', 6, -5],
            [$santris[7], 'Tidak mengikuti kegiatan', 'Ringan', 'Tidak hadir kerja bakti', 10, -3],
        ];

        foreach ($pelanggaran as [$santri, $jenis, $tingkat, $ket, $lalu, $poin]) {
            Pelanggaran::updateOrCreate(
                [
                    'santri_id' => $santri->id,
                    'jenis' => $jenis,
                    'tanggal' => $today->copy()->subDays($lalu)->toDateString(),
                ],
                [
                    'tingkat' => $tingkat,
                    'keterangan' => $ket,
                    'poin' => $poin,
                    'is_auto' => false,
                ]
            );
        }

        /* ---------------------------------------------------------------- */
        /* PELANGGARAN OTOMATIS (bagian 20) - mencegah duplikasi             */
        /* ---------------------------------------------------------------- */
        $oto = new AutoViolationService;
        foreach (array_keys(config('simpas.activities')) as $key) {
            $oto->check($key, $today->toDateString());
        }
    }

    /** Pilih status berdasarkan bobot persentase. */
    private function acak(array $bobot): string
    {
        $total = array_sum($bobot);
        $rand = random_int(1, $total);

        foreach ($bobot as $status => $b) {
            $rand -= $b;
            if ($rand <= 0) {
                return $status;
            }
        }

        return 'Hadir';
    }
}
