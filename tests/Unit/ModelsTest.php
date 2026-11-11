<?php

namespace Tests\Unit;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['db']->connection('sqlite')->getPdo()->sqliteCreateFunction(
            'now',
            fn () => now()->toDateTimeString()
        );

        return $app;
    }

    public function test_user_has_many_santri_dalam_urutan_nis(): void
    {
        $wali = $this->buatUser(User::ROLE_WALI);
        $santriB = $this->buatSantri(['nis' => 'NIS-002', 'wali_id' => $wali->id]);
        $santriA = $this->buatSantri(['nis' => 'NIS-001', 'wali_id' => $wali->id]);

        $this->assertSame(
            [$santriA->id, $santriB->id],
            $wali->santris->modelKeys()
        );
    }

    public function test_santri_belongs_to_user_wali_dari_wali_id(): void
    {
        $wali = $this->buatUser(User::ROLE_WALI);
        $santri = $this->buatSantri(['wali_id' => $wali->id]);

        $this->assertTrue($santri->wali->is($wali));
    }

    public function test_user_menentukan_role_dan_landing_route(): void
    {
        $wali = new User(['role' => User::ROLE_WALI]);
        $santri = new User(['role' => User::ROLE_SANTRI]);
        $admin = new User(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($wali->isWali());
        $this->assertTrue($wali->isWaliOrSantri());
        $this->assertSame('wali.dashboard', $wali->landingRoute());
        $this->assertTrue($santri->isSantri());
        $this->assertTrue($santri->isWaliOrSantri());
        $this->assertSame('wali.dashboard', $santri->landingRoute());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isWaliOrSantri());
        $this->assertSame('dashboard', $admin->landingRoute());
    }

    public function test_accessor_foto_url_mengembalikan_null_url_dan_path_storage(): void
    {
        $this->assertNull((new Santri(['foto' => null]))->foto_url);
        $this->assertSame(
            'https://contoh.test/foto.jpg',
            (new Santri(['foto' => 'https://contoh.test/foto.jpg']))->foto_url
        );
        $this->assertSame(
            '/foto/foto.jpg',
            (new Santri(['foto' => '/foto/foto.jpg']))->foto_url
        );
        $this->assertStringEndsWith(
            '/storage/foto/foto.jpg',
            (new Santri(['foto' => 'foto/foto.jpg']))->foto_url
        );
    }

    public function test_accessor_initial_santri_mendukung_nama_satu_dan_banyak_kata(): void
    {
        $this->assertSame('NA', (new Santri(['nama' => 'naeli']))->initials);
        $this->assertSame('LU', (new Santri(['nama' => 'Layinna Umami']))->initials);
    }

    public function test_accessor_level_diniyah_hanya_menampilkan_level_diniyah(): void
    {
        $diniyah = new Santri([
            'jenis_diniyah' => 'Diniyah',
            'level_diniyah' => "2 'Ulya",
        ]);
        $asatidzah = new Santri([
            'jenis_diniyah' => 'Asatidzah',
            'level_diniyah' => 'Level diabaikan',
        ]);

        $this->assertSame("2 'Ulya", $diniyah->level_diniyah_tampil);
        $this->assertSame('-', $asatidzah->level_diniyah_tampil);
    }

    public function test_scope_aktif_dan_cari_menyaring_santri_dengan_nama_atau_nis(): void
    {
        $cocokNama = $this->buatSantri(['nama' => 'Aisyah Putri']);
        $cocokNis = $this->buatSantri(['nis' => 'NIS-AISYAH']);
        $this->buatSantri(['nama' => 'Aisyah Alumni', 'status_aktif' => 'Alumni']);
        $this->buatSantri(['nama' => 'Aisyah Terhapus'])->delete();

        $hasil = Santri::aktif()->cari('Aisyah')->get();

        $this->assertEqualsCanonicalizing(
            [$cocokNama->id, $cocokNis->id],
            $hasil->modelKeys()
        );
    }

    public function test_scope_cari_tanpa_keyword_tidak_mengubah_query(): void
    {
        $pertama = $this->buatSantri(['nis' => 'NIS-001']);
        $kedua = $this->buatSantri(['nis' => 'NIS-002']);

        $this->assertEqualsCanonicalizing(
            [$pertama->id, $kedua->id],
            Santri::cari(null)->get()->modelKeys()
        );
    }

    private function buatUser(string $role): User
    {
        return User::create([
            'name' => 'Wali Test',
            'username' => 'akun-'.uniqid(),
            'password' => 'password-test',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function buatSantri(array $attributes = []): Santri
    {
        static $counter = 0;
        $counter++;

        return Santri::create(array_merge([
            'nis' => 'NIS-TEST-'.$counter,
            'nama' => 'Santri Test '.$counter,
            'jenis_kelamin' => 'Putri',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => 'Diniyah',
        ], $attributes));
    }
}
