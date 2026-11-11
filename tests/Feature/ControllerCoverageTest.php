<?php

namespace Tests\Feature;

use App\Models\Pelanggaran;
use App\Models\Prestasi;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControllerCoverageTest extends TestCase
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
    /* Prestasi */
    /* ------------------------------------------------------------------ */

    public function test_prestasi_index_menampilkan_daftar_dengan_filter(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P1', 'Budi Prestasi');
        Prestasi::create($this->dataPrestasi($santri));

        $this->actingAs($admin)->get(route('prestasi.index'))->assertOk();
        $this->actingAs($admin)->get(route('prestasi.index', [
            'q' => 'Budi',
            'jenis' => $this->master('jenis_prestasi'),
            'tingkat' => $this->master('tingkat_prestasi'),
        ]))->assertOk();
    }

    public function test_prestasi_index_wali_dialihkan_ke_halaman_wali(): void
    {
        $wali = $this->buatWali('wali-prestasi');

        $this->actingAs($wali)
            ->get(route('prestasi.index'))
            ->assertRedirect(route('wali.prestasi'));
    }

    public function test_prestasi_halaman_create_edit_dan_show_terbuka_untuk_admin(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P2', 'Santri Form');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));

        $this->actingAs($admin)->get(route('prestasi.create'))->assertOk();
        $this->actingAs($admin)->get(route('prestasi.edit', $prestasi))->assertOk();
        $this->actingAs($admin)->get(route('prestasi.show', $prestasi))->assertOk();
    }

    public function test_prestasi_store_menyimpan_data_dengan_poin_positif(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P3', 'Santri Juara');

        $this->actingAs($admin)
            ->post(route('prestasi.store'), $this->dataPrestasi($santri, [
                'nama_prestasi' => 'Juara Pidato',
                'poin' => 15,
            ]))
            ->assertRedirect(route('prestasi.index'));

        $this->assertDatabaseHas('prestasis', [
            'santri_id' => $santri->id,
            'nama_prestasi' => 'Juara Pidato',
            'poin' => 15,
        ]);
    }

    public function test_prestasi_store_menyimpan_berkas_bukti(): void
    {
        Storage::fake('public');
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P4', 'Santri Bukti');

        $this->actingAs($admin)
            ->post(route('prestasi.store'), $this->dataPrestasi($santri, [
                'bukti' => $this->gambarPalsu(),
            ]))
            ->assertRedirect(route('prestasi.index'));

        $prestasi = Prestasi::firstOrFail();
        $this->assertNotNull($prestasi->bukti);
        Storage::disk('public')->assertExists($prestasi->bukti);
    }

    public function test_prestasi_store_menolak_data_tidak_valid(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P5', 'Santri Invalid');

        $this->actingAs($admin)
            ->post(route('prestasi.store'), $this->dataPrestasi($santri, [
                'nama_prestasi' => '',
                'poin' => -5,
            ]))
            ->assertSessionHasErrors(['nama_prestasi', 'poin']);

        $this->assertDatabaseCount('prestasis', 0);
    }

    public function test_prestasi_update_mengubah_data_dan_bukti(): void
    {
        Storage::fake('public');
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P6', 'Santri Update');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));

        $this->actingAs($admin)
            ->put(route('prestasi.update', $prestasi), $this->dataPrestasi($santri, [
                'nama_prestasi' => 'Nama Sudah Diubah',
                'poin' => 25,
                'bukti' => $this->gambarPalsu(),
            ]))
            ->assertRedirect(route('prestasi.index'));

        $prestasi->refresh();
        $this->assertSame('Nama Sudah Diubah', $prestasi->nama_prestasi);
        $this->assertSame(25, $prestasi->poin);
        Storage::disk('public')->assertExists($prestasi->bukti);
    }

    public function test_prestasi_destroy_memindahkan_data_ke_sampah(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-P7', 'Santri Hapus');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));

        $this->actingAs($admin)
            ->delete(route('prestasi.destroy', $prestasi))
            ->assertRedirect();

        $this->assertSoftDeleted('prestasis', ['id' => $prestasi->id]);
    }

    public function test_wali_tidak_bisa_menyimpan_prestasi(): void
    {
        $wali = $this->buatWali('wali-store-prestasi');
        $santri = $this->buatSantri('NIS-P8', 'Santri Wali');

        $response = $this->actingAs($wali)
            ->post(route('prestasi.store'), $this->dataPrestasi($santri));

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertDatabaseCount('prestasis', 0);
    }

    /* ------------------------------------------------------------------ */
    /* Pelanggaran */
    /* ------------------------------------------------------------------ */

    public function test_pelanggaran_index_menampilkan_daftar_dengan_filter(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-V1', 'Budi Pelanggar');
        Pelanggaran::create($this->dataPelanggaran($santri, ['poin' => -3]));

        $this->actingAs($admin)->get(route('pelanggaran.index'))->assertOk();
        $this->actingAs($admin)->get(route('pelanggaran.index', [
            'q' => 'Budi',
            'tingkat' => $this->master('tingkat_pelanggaran'),
            'jenis' => $this->master('jenis_pelanggaran'),
        ]))->assertOk();
    }

    public function test_pelanggaran_index_wali_dialihkan_ke_halaman_wali(): void
    {
        $wali = $this->buatWali('wali-pelanggaran');

        $this->actingAs($wali)
            ->get(route('pelanggaran.index'))
            ->assertRedirect(route('wali.pelanggaran'));
    }

    public function test_pelanggaran_halaman_create_edit_dan_show_terbuka_untuk_admin(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-V2', 'Santri Form');
        $pelanggaran = Pelanggaran::create($this->dataPelanggaran($santri, ['poin' => -3]));

        $this->actingAs($admin)->get(route('pelanggaran.create'))->assertOk();
        $this->actingAs($admin)->get(route('pelanggaran.edit', $pelanggaran))->assertOk();
        $this->actingAs($admin)->get(route('pelanggaran.show', $pelanggaran))->assertOk();
    }

    public function test_pelanggaran_store_selalu_menyimpan_poin_negatif(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-V3', 'Santri Pelanggar');

        $this->actingAs($admin)
            ->post(route('pelanggaran.store'), $this->dataPelanggaran($santri, ['poin' => 5]))
            ->assertRedirect(route('pelanggaran.index'));

        $this->assertDatabaseHas('pelanggarans', [
            'santri_id' => $santri->id,
            'poin' => -5,
            'is_auto' => false,
        ]);
    }

    public function test_pelanggaran_store_menolak_poin_nol(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-V4', 'Santri Invalid');

        $this->actingAs($admin)
            ->post(route('pelanggaran.store'), $this->dataPelanggaran($santri, ['poin' => 0]))
            ->assertSessionHasErrors('poin');

        $this->assertDatabaseCount('pelanggarans', 0);
    }

    public function test_pelanggaran_update_dan_destroy(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-V5', 'Santri Update');
        $pelanggaran = Pelanggaran::create($this->dataPelanggaran($santri, ['poin' => -3]));

        $this->actingAs($admin)
            ->put(route('pelanggaran.update', $pelanggaran), $this->dataPelanggaran($santri, ['poin' => 7]))
            ->assertRedirect(route('pelanggaran.index'));

        $this->assertSame(-7, $pelanggaran->fresh()->poin);

        $this->actingAs($admin)
            ->delete(route('pelanggaran.destroy', $pelanggaran))
            ->assertRedirect();

        $this->assertSoftDeleted('pelanggarans', ['id' => $pelanggaran->id]);
    }

    public function test_wali_tidak_bisa_menyimpan_pelanggaran(): void
    {
        $wali = $this->buatWali('wali-store-pelanggaran');
        $santri = $this->buatSantri('NIS-V6', 'Santri Wali');

        $response = $this->actingAs($wali)
            ->post(route('pelanggaran.store'), $this->dataPelanggaran($santri));

        $this->assertContains($response->getStatusCode(), [302, 403]);
        $this->assertDatabaseCount('pelanggarans', 0);
    }

    /* ------------------------------------------------------------------ */
    /* Pengaturan */
    /* ------------------------------------------------------------------ */

    public function test_pengaturan_index_terbuka_untuk_admin_dan_ditolak_untuk_wali(): void
    {
        $admin = $this->buatAdmin();
        $wali = $this->buatWali('wali-pengaturan');

        $this->actingAs($admin)->get(route('pengaturan.index'))->assertOk();
        $this->actingAs($wali)->get(route('pengaturan.index'))->assertRedirect(route('dashboard'));
    }

    public function test_pengaturan_simpan_menyimpan_poin_dan_nama_aplikasi(): void
    {
        $admin = $this->buatAdmin();
        $points = array_fill_keys(array_keys(config('simpas.points')), 2);

        $this->actingAs($admin)
            ->post(route('pengaturan.simpan'), [
                'points' => $points,
                'auto_enabled' => '1',
                'nama_aplikasi' => 'SIMPAS Uji',
            ])
            ->assertRedirect(route('pengaturan.index'));

        $this->assertSame('SIMPAS Uji', Setting::get('nama_aplikasi'));
        $this->assertSame('1', Setting::get('auto_violation_enabled'));
        $this->assertSame('SIMPAS Uji', config('simpas.nama_aplikasi'));
    }

    public function test_pengaturan_simpan_menolak_poin_di_luar_batas(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('pengaturan.simpan'), ['points' => ['x' => 99]])
            ->assertSessionHasErrors('points.x');
    }

    public function test_hapus_semua_data_wajib_konfirmasi(): void
    {
        $admin = $this->buatAdmin();
        $this->buatSantri('NIS-H1', 'Santri Aman');

        $this->actingAs($admin)
            ->post(route('pengaturan.hapus-semua-data'), [])
            ->assertSessionHasErrors('konfirmasi');

        $this->actingAs($admin)
            ->post(route('pengaturan.hapus-semua-data'), ['konfirmasi' => 'hapus'])
            ->assertSessionHasErrors('konfirmasi');

        $this->assertDatabaseCount('santris', 1);
    }

    public function test_hapus_semua_data_menghapus_santri_tetapi_akun_tetap(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-H2', 'Santri Dihapus');
        Prestasi::create($this->dataPrestasi($santri));
        Pelanggaran::create($this->dataPelanggaran($santri, ['poin' => -3]));

        $this->actingAs($admin)
            ->post(route('pengaturan.hapus-semua-data'), ['konfirmasi' => 'HAPUS SEMUA'])
            ->assertRedirect(route('pengaturan.index'));

        $this->assertDatabaseCount('santris', 0);
        $this->assertDatabaseCount('prestasis', 0);
        $this->assertDatabaseCount('pelanggarans', 0);
        $this->assertDatabaseHas('users', ['username' => 'admin-cov']);
    }

    /* ------------------------------------------------------------------ */
    /* Sampah */
    /* ------------------------------------------------------------------ */

    public function test_sampah_index_menampilkan_semua_jenis_dan_filter_tipe(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S1', 'Santri Sampah');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));
        $prestasi->delete();
        $santri->delete();

        $this->actingAs($admin)->get(route('sampah.index'))->assertOk();
        $this->actingAs($admin)->get(route('sampah.index', ['tipe' => 'santri']))->assertOk();
        $this->actingAs($admin)->get(route('sampah.index', ['tipe' => 'prestasi']))->assertOk();
        $this->actingAs($admin)->get(route('sampah.index', [
            'tipe' => array_key_first(config('simpas.activities')),
        ]))->assertOk();
        $this->actingAs($admin)->get(route('sampah.index', ['tipe' => 'tidak-ada']))->assertOk();
    }

    public function test_sampah_restore_memulihkan_data(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S2', 'Santri Pulih');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));
        $prestasi->delete();

        $this->actingAs($admin)
            ->post(route('sampah.restore', ['type' => 'prestasi', 'id' => $prestasi->id]))
            ->assertRedirect();

        $this->assertNotSoftDeleted('prestasis', ['id' => $prestasi->id]);
    }

    public function test_sampah_restore_dan_permanen_mengembalikan_404_untuk_data_tidak_ada(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('sampah.restore', ['type' => 'tidak-ada', 'id' => 1]))
            ->assertNotFound();
        $this->actingAs($admin)
            ->post(route('sampah.restore', ['type' => 'prestasi', 'id' => 999999]))
            ->assertNotFound();
        $this->actingAs($admin)
            ->delete(route('sampah.permanen', ['type' => 'tidak-ada', 'id' => 1]))
            ->assertNotFound();
        $this->actingAs($admin)
            ->delete(route('sampah.permanen', ['type' => 'prestasi', 'id' => 999999]))
            ->assertNotFound();
    }

    public function test_sampah_permanen_menghapus_data_selamanya(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S3', 'Santri Permanen');
        $prestasi = Prestasi::create($this->dataPrestasi($santri));
        $prestasi->delete();

        $this->actingAs($admin)
            ->delete(route('sampah.permanen', ['type' => 'prestasi', 'id' => $prestasi->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('prestasis', ['id' => $prestasi->id]);
    }

    public function test_sampah_hapus_semua_menghapus_data_yang_ada_di_sampah(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S4', 'Santri Hapus Semua');
        $terhapus = Prestasi::create($this->dataPrestasi($santri));
        $terhapus->delete();

        $this->actingAs($admin)
            ->delete(route('sampah.hapus-semua', ['type' => 'prestasi']))
            ->assertRedirect(route('sampah.index', ['tipe' => 'prestasi']));

        $this->assertDatabaseMissing('prestasis', ['id' => $terhapus->id]);
    }

    public function test_sampah_hapus_semua_tidak_menghapus_data_aktif(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-S5', 'Santri Aktif');
        $aktif = Prestasi::create($this->dataPrestasi($santri));
        $terhapus = Prestasi::create($this->dataPrestasi($santri, ['nama_prestasi' => 'Di Sampah']));
        $terhapus->delete();

        $this->actingAs($admin)
            ->delete(route('sampah.hapus-semua', ['type' => 'prestasi']));

        $this->assertDatabaseHas('prestasis', ['id' => $aktif->id, 'deleted_at' => null]);
    }

    public function test_wali_tidak_bisa_membuka_sampah(): void
    {
        $wali = $this->buatWali('wali-sampah');

        $this->actingAs($wali)->get(route('sampah.index'))->assertRedirect(route('dashboard'));
    }

    /* ------------------------------------------------------------------ */
    /* Dashboard */
    /* ------------------------------------------------------------------ */

    public function test_dashboard_admin_menampilkan_ringkasan(): void
    {
        $admin = $this->buatAdmin();
        $santri = $this->buatSantri('NIS-D1', 'Santri Dashboard');
        Prestasi::create($this->dataPrestasi($santri));
        Pelanggaran::create($this->dataPelanggaran($santri, ['poin' => -3]));

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_wali_dan_santri_dialihkan_ke_dashboard_wali(): void
    {
        $wali = $this->buatWali('wali-dashboard');
        $santri = $this->buatSantri('NIS-D2', 'Santri Akun');
        $akunSantri = User::create([
            'name' => $santri->nama,
            'username' => $santri->nis,
            'password' => Hash::make('santri-password'),
            'role' => User::ROLE_SANTRI,
            'santri_id' => $santri->id,
            'is_active' => true,
        ]);

        $this->actingAs($wali)->get(route('dashboard'))->assertRedirect(route('wali.dashboard'));
        $this->actingAs($akunSantri)->get(route('dashboard'))->assertRedirect(route('wali.dashboard'));
    }

    public function test_dashboard_mengalihkan_tamu_ke_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /* ------------------------------------------------------------------ */
    /* Helper */
    /* ------------------------------------------------------------------ */

    private function buatAdmin(): User
    {
        return User::create([
            'name' => 'Admin Coverage',
            'username' => 'admin-cov',
            'password' => Hash::make('admin-password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function buatWali(string $username): User
    {
        return User::create([
            'name' => 'Wali '.$username,
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('wali-password'),
            'role' => User::ROLE_WALI,
            'is_active' => true,
        ]);
    }

    private function buatSantri(string $nis, string $nama): Santri
    {
        return Santri::create([
            'nis' => $nis,
            'nama' => $nama,
            'jenis_kelamin' => 'Putra',
            'status_aktif' => 'Aktif',
        ]);
    }

    private function master(string $key): string
    {
        return (string) array_values(config('simpas.master.'.$key))[0];
    }

    private function dataPrestasi(Santri $santri, array $override = []): array
    {
        return array_merge([
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'jenis' => $this->master('jenis_prestasi'),
            'nama_prestasi' => 'Juara Lomba',
            'tingkat' => $this->master('tingkat_prestasi'),
            'poin' => 10,
        ], $override);
    }

    private function dataPelanggaran(Santri $santri, array $override = []): array
    {
        return array_merge([
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'jenis' => $this->master('jenis_pelanggaran'),
            'tingkat' => $this->master('tingkat_pelanggaran'),
            'poin' => 3,
        ], $override);
    }

    private function gambarPalsu(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent('bukti.png', $png);
    }
}
