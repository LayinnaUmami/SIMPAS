<?php

namespace Tests\Feature;

use App\Models\Khataman;
use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WaliProfileRankingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        DB::connection('sqlite')->getPdo()->sqliteCreateFunction(
            'now',
            fn () => now()->toDateTimeString()
        );

        return $app;
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: dashboard */
    /* ------------------------------------------------------------------ */

    public function test_dashboard_wali_menampilkan_data_anak(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        Prestasi::create($this->dataPrestasi($anak));
        Pelanggaran::create($this->dataPelanggaran($anak));

        $this->actingAs($wali)->get(route('wali.dashboard'))->assertOk();
    }

    public function test_dashboard_wali_dengan_anak_non_diniyah(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali, ['jenis_diniyah' => 'Mutimin']);

        $this->actingAs($wali)->get(route('wali.dashboard'))->assertOk();
    }

    public function test_dashboard_wali_dengan_data_aktivitas_hari_ini(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        foreach (array_keys(config('simpas.activities')) as $key) {
            $this->buatAktivitas($key, $anak, now()->toDateString());
        }

        $this->actingAs($wali)->get(route('wali.dashboard'))->assertOk();
    }

    public function test_wali_tanpa_anak_ditolak(): void
    {
        $wali = $this->buatWali();

        $this->actingAs($wali)->get(route('wali.dashboard'))->assertForbidden();
    }

    public function test_akun_santri_bisa_membuka_dashboard_wali(): void
    {
        $anak = $this->buatSantri();
        $akun = User::create([
            'name' => 'Akun Santri',
            'username' => 'santri-'.uniqid(),
            'password' => Hash::make('santri-password'),
            'role' => User::ROLE_SANTRI,
            'santri_id' => $anak->id,
            'is_active' => true,
        ]);

        $this->actingAs($akun)->get(route('wali.dashboard'))->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: pilih anak */
    /* ------------------------------------------------------------------ */

    public function test_wali_bisa_mengganti_anak_yang_ditampilkan(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);
        $anak2 = $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.pilih-santri'), ['santri_id' => $anak2->id])
            ->assertRedirect(route('wali.dashboard'))
            ->assertSessionHas('wali_santri_terpilih', $anak2->id);

        $this->actingAs($wali)->get(route('wali.dashboard'))->assertOk();
    }

    public function test_wali_tidak_bisa_memilih_anak_milik_wali_lain(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);
        $anakLain = $this->buatSantri($this->buatWali());

        $this->actingAs($wali)
            ->post(route('wali.pilih-santri'), ['santri_id' => $anakLain->id])
            ->assertForbidden();
    }

    public function test_pilih_santri_menolak_input_tidak_valid(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.pilih-santri'), [])
            ->assertSessionHasErrors('santri_id');
        $this->actingAs($wali)
            ->post(route('wali.pilih-santri'), ['santri_id' => 'abc'])
            ->assertSessionHasErrors('santri_id');
    }

    public function test_pilihan_anak_yang_sudah_tidak_valid_dibuang_dari_sesi(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->withSession(['wali_santri_terpilih' => 999999])
            ->actingAs($wali)
            ->get(route('wali.dashboard'))
            ->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: aktivitas */
    /* ------------------------------------------------------------------ */

    public function test_aktivitas_wali_semua_jenis_dan_filter_tanggal(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.aktivitas'))->assertOk();

        foreach (array_keys(config('simpas.activities')) as $key) {
            $this->actingAs($wali)->get(route('wali.aktivitas', ['type' => $key]))->assertOk();
        }

        $this->actingAs($wali)->get(route('wali.aktivitas', ['tanggal' => '2024-01-15']))->assertOk();
        $this->actingAs($wali)->get(route('wali.aktivitas', ['tanggal' => 'bukan-tanggal']))->assertOk();
        $this->actingAs($wali)->get(route('wali.aktivitas', ['tanggal' => ['x']]))->assertOk();
    }

    public function test_aktivitas_wali_menampilkan_baris_data(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        $key = array_key_first(config('simpas.activities'));
        $this->buatAktivitas($key, $anak, now()->toDateString());

        $this->actingAs($wali)->get(route('wali.aktivitas', ['type' => $key]))->assertOk();
    }

    public function test_aktivitas_wali_jenis_tidak_dikenal_adalah_404(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.aktivitas', ['type' => 'tidak-ada']))->assertNotFound();
    }

    public function test_aktivitas_diniyah_tidak_tersedia_untuk_santri_non_diniyah(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali, ['jenis_diniyah' => 'Mutimin']);

        $this->actingAs($wali)->get(route('wali.aktivitas', ['type' => 'diniyah']))->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: prestasi, pelanggaran, khataman */
    /* ------------------------------------------------------------------ */

    public function test_prestasi_wali_dengan_berbagai_filter(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        Prestasi::create($this->dataPrestasi($anak));

        $this->actingAs($wali)->get(route('wali.prestasi'))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'all']))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'semua']))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'tahun', 'tahun' => now()->year]))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'tahun', 'tahun' => 'abc']))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'tahun', 'tahun' => 3000]))->assertOk();
        $this->actingAs($wali)->get(route('wali.prestasi', ['periode' => 'tidak-valid']))->assertOk();
    }

    public function test_pelanggaran_wali_dengan_semua_periode(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        Pelanggaran::create($this->dataPelanggaran($anak));

        foreach (['semua', 'hari', 'minggu', 'bulan', 'tahun', 'tidak-valid'] as $periode) {
            $this->actingAs($wali)->get(route('wali.pelanggaran', ['periode' => $periode]))->assertOk();
        }

        $this->actingAs($wali)->get(route('wali.pelanggaran', [
            'periode' => 'custom',
            'mulai' => now()->subMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]))->assertOk();
        $this->actingAs($wali)->get(route('wali.pelanggaran', [
            'periode' => 'minggu',
            'minggu' => 99,
            'bulan' => 99,
        ]))->assertOk();
    }

    public function test_khataman_wali_dengan_dan_tanpa_data(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.khataman'))->assertOk();

        Khataman::unguarded(fn () => Khataman::create([
            'santri_id' => $anak->id,
            'tahun' => now()->year, 'jenis_khatam' => array_values(config('simpas.master.jenis_khatam', ['Binnadhor']))[0],
        ]));

        $this->actingAs($wali)->get(route('wali.khataman'))->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: laporan, santri, ranking */
    /* ------------------------------------------------------------------ */

    public function test_laporan_wali_semua_jenis_laporan(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        Prestasi::create($this->dataPrestasi($anak));
        Pelanggaran::create($this->dataPelanggaran($anak));
        $jenisList = array_merge(array_keys(config('simpas.activities')), ['prestasi', 'pelanggaran']);

        foreach ($jenisList as $jenis) {
            $this->actingAs($wali)->get(route('wali.laporan', ['jenis' => $jenis]))->assertOk();
        }

        $this->actingAs($wali)->get(route('wali.laporan'))->assertOk();
    }

    public function test_laporan_wali_dengan_data_aktivitas_dan_periode(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        $key = array_key_first(config('simpas.activities'));
        $this->buatAktivitas($key, $anak, now()->toDateString());
        $this->buatAktivitas($key, $anak, now()->subDay()->toDateString(), 'Alfa');

        foreach (['bulan', 'hari', 'minggu', 'tahun', 'tidak-valid'] as $periode) {
            $this->actingAs($wali)->get(route('wali.laporan', [
                'jenis' => $key,
                'periode' => $periode,
            ]))->assertOk();
        }

        $this->actingAs($wali)->get(route('wali.laporan', [
            'jenis' => $key,
            'periode' => 'custom',
            'mulai' => now()->subMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]))->assertOk();
    }

    public function test_laporan_wali_jenis_tidak_dikenal_adalah_404(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.laporan', ['jenis' => 'tidak-ada']))->assertNotFound();
    }

    public function test_halaman_data_santri_wali(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.santri'))->assertOk();
    }

    public function test_ranking_wali_dengan_semua_periode(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);
        $this->buatSantri(null, ['nama' => 'Santri Lain']);

        foreach (['bulan', 'minggu', 'tahun', 'semua', 'all', 'tidak-valid'] as $periode) {
            $this->actingAs($wali)->get(route('wali.ranking', ['periode' => $periode]))->assertOk();
        }

        $this->actingAs($wali)->get(route('wali.ranking', [
            'periode' => 'custom',
            'mulai' => now()->subMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]))->assertOk();
    }

    /* ------------------------------------------------------------------ */
    /* WaliController: profil & password */
    /* ------------------------------------------------------------------ */

    public function test_profil_wali_dapat_dibuka_dan_diperbarui(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)->get(route('wali.profil'))->assertOk();

        $this->actingAs($wali)
            ->post(route('wali.profil.update'), [
                'name' => 'Nama Wali Baru',
                'email' => 'baru-'.uniqid().'@example.test',
                'telepon' => '08123456789',
            ])
            ->assertSessionHas('toast');

        $this->assertSame('Nama Wali Baru', $wali->fresh()->name);
    }

    public function test_profil_wali_menyimpan_foto(): void
    {
        Storage::fake('public');
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.profil.update'), [
                'name' => 'Wali Foto',
                'foto' => $this->gambarPalsu(),
            ])
            ->assertSessionHas('toast');

        $this->assertNotNull($wali->fresh()->foto);
        Storage::disk('public')->assertExists($wali->fresh()->foto);
    }

    public function test_profil_wali_menolak_data_tidak_valid(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.profil.update'), ['name' => '', 'email' => 'bukan-email'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_wali_dapat_mengganti_password(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.profil.password'), [
                'current_password' => 'wali-password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('toast');

        $this->assertTrue(Hash::check('password-baru-123', $wali->fresh()->password));
    }

    public function test_wali_gagal_mengganti_password_jika_password_lama_salah(): void
    {
        $wali = $this->buatWali();
        $this->buatSantri($wali);

        $this->actingAs($wali)
            ->post(route('wali.profil.password'), [
                'current_password' => 'salah',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasErrors('current_password');
    }

    /* ------------------------------------------------------------------ */
    /* ProfileController (admin) */
    /* ------------------------------------------------------------------ */

    public function test_profile_index_terbuka_untuk_admin(): void
    {
        $this->actingAs($this->buatAdmin())->get(route('profile.index'))->assertOk();
    }

    public function test_profile_update_menyimpan_data(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('profile.update'), [
                'name' => 'Admin Baru',
                'email' => 'admin-'.uniqid().'@example.test',
                'telepon' => '0800000000',
            ])
            ->assertSessionHas('toast');

        $this->assertSame('Admin Baru', $admin->fresh()->name);
    }

    public function test_profile_update_menyimpan_foto(): void
    {
        Storage::fake('public');
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('profile.update'), [
                'name' => 'Admin Foto',
                'foto' => $this->gambarPalsu(),
            ])
            ->assertSessionHas('toast');

        Storage::disk('public')->assertExists($admin->fresh()->foto);
    }

    public function test_profile_update_menolak_data_tidak_valid(): void
    {
        $this->actingAs($this->buatAdmin())
            ->post(route('profile.update'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_profile_password_berhasil_dan_gagal(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('profile.password'), [
                'current_password' => 'salah',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($admin)
            ->post(route('profile.password'), [
                'current_password' => 'admin-password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $admin->fresh()->password));
    }

    public function test_profile_password_menolak_password_pendek(): void
    {
        $this->actingAs($this->buatAdmin())
            ->post(route('profile.password'), [
                'current_password' => 'admin-password',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertSessionHasErrors('password');
    }

    /* ------------------------------------------------------------------ */
    /* RankingController */
    /* ------------------------------------------------------------------ */

    public function test_ranking_admin_dengan_filter_dan_semua_periode(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSantri(null, ['nama' => 'Santri Ranking', 'kamar' => 'A1']);

        $this->actingAs($admin)->get(route('ranking.index'))->assertOk();

        foreach (['bulan', 'minggu', 'tahun', 'semua'] as $periode) {
            $this->actingAs($admin)->get(route('ranking.index', ['periode' => $periode]))->assertOk();
        }

        $this->actingAs($admin)->get(route('ranking.index', [
            'periode' => 'custom',
            'mulai' => now()->subMonth()->toDateString(),
            'akhir' => now()->toDateString(),
        ]))->assertOk();
        $this->actingAs($admin)->get(route('ranking.index', [
            'q' => 'Ranking',
            'jenis_kelamin' => 'Putra',
            'kamar' => 'A1',
        ]))->assertOk();
    }

    public function test_ranking_wali_dan_santri_dialihkan_ke_ranking_wali(): void
    {
        $wali = $this->buatWali();
        $anak = $this->buatSantri($wali);
        $akunSantri = User::create([
            'name' => 'Akun Santri',
            'username' => 'santri-'.uniqid(),
            'password' => Hash::make('santri-password'),
            'role' => User::ROLE_SANTRI,
            'santri_id' => $anak->id,
            'is_active' => true,
        ]);

        $this->actingAs($wali)->get(route('ranking.index'))->assertRedirect();
        $this->actingAs($akunSantri)->get(route('ranking.index', ['periode' => 'bulan']))->assertRedirect();
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    private function buatAdmin(): User
    {
        return User::create([
            'name' => 'Admin Uji',
            'username' => 'admin-'.uniqid(),
            'password' => Hash::make('admin-password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function buatWali(): User
    {
        $username = 'wali-'.uniqid();

        return User::create([
            'name' => 'Wali '.$username,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('wali-password'),
            'role' => User::ROLE_WALI,
            'is_active' => true,
        ]);
    }

    private function buatSantri(?User $wali = null, array $override = []): Santri
    {
        return Santri::unguarded(fn () => Santri::create(array_merge([
            'nis' => 'NIS-'.uniqid(),
            'nama' => 'Anak Uji',
            'jenis_kelamin' => 'Putra',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => 'Diniyah',
            'wali_id' => $wali?->id,
        ], $override)));
    }

    private function buatAktivitas(string $key, Santri $santri, string $tanggal, string $status = 'Hadir'): void
    {
        $cfg = config('simpas.activities')[$key];
        $model = $cfg['model'];
        $sesi = $cfg['sesi'][0] ?? $cfg['sessions'][0] ?? 1;

        $model::unguarded(fn () => $model::create([
            'santri_id' => $santri->id,
            'tanggal' => $tanggal,
            'sesi' => $sesi,
            'status' => $status,
            'poin' => 1,
        ]));
    }

    private function master(string $key): string
    {
        return (string) array_values(config('simpas.master.'.$key))[0];
    }

    private function dataPrestasi(Santri $santri): array
    {
        return [
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'jenis' => $this->master('jenis_prestasi'),
            'nama_prestasi' => 'Juara Lomba',
            'tingkat' => $this->master('tingkat_prestasi'),
            'poin' => 10,
        ];
    }

    private function dataPelanggaran(Santri $santri): array
    {
        return [
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'jenis' => $this->master('jenis_pelanggaran'),
            'tingkat' => $this->master('tingkat_pelanggaran'),
            'poin' => -3,
        ];
    }

    private function gambarPalsu(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}
