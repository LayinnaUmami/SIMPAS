<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WaliAccountTest extends TestCase
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

    public function test_creating_santri_creates_a_wali_account_and_shows_it_in_accounts(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->get(route('santri.create'))
            ->assertOk()
            ->assertSee('Tautkan ke wali yang sudah ada')
            ->assertSee('Pilih jika kakak/adiknya sudah terdaftar di pesantren.');

        $response = $this->actingAs($admin)->post(route('santri.store'), $this->dataSantri([
            'nis' => 'NIS-BARU-1',
            'nama' => 'Santri Baru Pertama',
            'nama_wali' => 'Budi Santoso',
            'wali_username' => 'wali-budi',
            'wali_password' => 'password-wali',
        ]));

        $santri = Santri::where('nis', 'NIS-BARU-1')->firstOrFail();
        $wali = User::where('username', 'wali-budi')->firstOrFail();

        $response->assertRedirect(route('santri.show', $santri));
        $this->assertSame((int) $wali->id, (int) $santri->wali_id);
        $this->assertSame('Budi Santoso', $wali->name);
        $this->assertSame(User::ROLE_WALI, $wali->role);
        $this->assertTrue(Hash::check('password-wali', $wali->password));
        $this->assertNotNull($wali->email_verified_at);

        $this->actingAs($admin)
            ->get(route('akun.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('wali-budi')
            ->assertSee('Santri Baru Pertama');

        $this->post(route('logout'));
        $this->post(route('login.attempt'), [
            'username' => 'wali-budi',
            'password' => 'password-wali',
        ])->assertRedirect(route('wali.dashboard'));
        $this->assertAuthenticatedAs($wali);
    }

    public function test_selecting_existing_wali_links_second_child_without_creating_account(): void
    {
        $admin = $this->buatAdmin();
        $wali = $this->buatWali('wali-keluarga', 'Wali Keluarga');
        $anakPertama = $this->buatSantri('NIS-KELUARGA-1', 'Anak Pertama', $wali);

        $this->actingAs($admin)
            ->post(route('santri.store'), $this->dataSantri([
                'nis' => 'NIS-KELUARGA-2',
                'nama' => 'Anak Kedua',
                'wali_id' => $wali->id,
                'wali_username' => 'abaikan-kode',
                'wali_password' => 'abaikan-password',
            ]))
            ->assertRedirect();

        $anakKedua = Santri::where('nis', 'NIS-KELUARGA-2')->firstOrFail();
        $this->assertDatabaseMissing('users', ['username' => 'abaikan-kode']);
        $this->assertSame((int) $wali->id, (int) $anakKedua->wali_id);
        $this->assertSame((int) $wali->id, (int) $anakPertama->fresh()->wali_id);
        $this->assertCount(2, $wali->fresh()->santris);

        $this->actingAs($wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('Anak Pertama')
            ->assertSee('Anak Kedua');
    }

    public function test_missing_or_non_wali_wali_id_is_rejected(): void
    {
        $admin = $this->buatAdmin();

        foreach ([999999, $admin->id] as $index => $waliId) {
            $this->actingAs($admin)
                ->post(route('santri.store'), $this->dataSantri([
                    'nis' => 'NIS-WALI-TIDAK-VALID-'.$index,
                    'nama' => 'Santri Tidak Valid',
                    'wali_id' => $waliId,
                    'wali_username' => 'ignored-code',
                    'wali_password' => 'ignored-password',
                ]))
                ->assertSessionHasErrors('wali_id');
        }

        $this->assertDatabaseMissing('santris', ['nis' => 'NIS-WALI-TIDAK-VALID-0']);
        $this->assertDatabaseMissing('santris', ['nis' => 'NIS-WALI-TIDAK-VALID-1']);
    }

    public function test_duplicate_new_wali_code_is_rejected(): void
    {
        $admin = $this->buatAdmin();
        $this->buatWali('kode-dipakai', 'Wali Lama');

        $this->actingAs($admin)
            ->post(route('santri.store'), $this->dataSantri([
                'nis' => 'NIS-KODE-DUPLIKAT',
                'nama' => 'Santri Duplikat',
                'wali_username' => 'kode-dipakai',
                'wali_password' => 'password-wali',
            ]))
            ->assertSessionHasErrors('wali_username');

        $this->assertDatabaseMissing('santris', ['nis' => 'NIS-KODE-DUPLIKAT']);
    }

    public function test_editing_santri_can_move_and_unlink_wali(): void
    {
        $admin = $this->buatAdmin();
        $waliLama = $this->buatWali('wali-lama', 'Wali Lama');
        $waliBaru = $this->buatWali('wali-baru', 'Wali Baru');
        $santri = $this->buatSantri('NIS-PINDAH', 'Santri Pindah', $waliLama);

        $this->actingAs($admin)
            ->put(route('santri.update', $santri), $this->dataSantri([
                'nis' => $santri->nis,
                'nama' => $santri->nama,
                'wali_id' => $waliBaru->id,
            ]))
            ->assertRedirect(route('santri.show', $santri));

        $this->assertSame((int) $waliBaru->id, (int) $santri->fresh()->wali_id);
        $this->assertNull($waliLama->fresh()->santri_id);
        $this->assertSame((int) $santri->id, (int) $waliBaru->fresh()->santri_id);

        $this->actingAs($admin)
            ->put(route('santri.update', $santri), $this->dataSantri([
                'nis' => $santri->nis,
                'nama' => $santri->nama,
                'wali_id' => '',
            ]))
            ->assertRedirect(route('santri.show', $santri));

        $this->assertNull($santri->fresh()->wali_id);
        $this->assertNull($waliBaru->fresh()->santri_id);
    }

    public function test_editing_wali_password_and_child_links_updates_existing_account(): void
    {
        $admin = $this->buatAdmin();
        $wali = $this->buatWali('wali-edit', 'Nama Lama', 'password-lama');
        $anakDilepas = $this->buatSantri('NIS-DILEPAS', 'Anak Dilepas', $wali);
        $anakDipertahankan = $this->buatSantri('NIS-DIPERTAHANKAN', 'Anak Dipertahankan', $wali);

        $this->actingAs($admin)
            ->get(route('akun.edit', $wali))
            ->assertOk()
            ->assertSee('Ganti Password (opsional)')
            ->assertSee('Pilih satu atau beberapa anak');

        $this->actingAs($admin)->put(route('akun.update', $wali), [
            'name' => 'Nama Baru',
            'username' => 'wali-edit-baru',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'santri_ids' => [$anakDipertahankan->id],
            'is_active' => '1',
        ])->assertRedirect(route('akun.index'));

        $wali->refresh();
        $this->assertSame('Nama Baru', $wali->name);
        $this->assertSame('wali-edit-baru', $wali->username);
        $this->assertTrue(Hash::check('password-baru', $wali->password));
        $this->assertFalse(Hash::check('password-lama', $wali->password));
        $this->assertNull($anakDilepas->fresh()->wali_id);
        $this->assertSame((int) $wali->id, (int) $anakDipertahankan->fresh()->wali_id);
    }

    public function test_deleting_wali_unlinks_but_does_not_delete_santri(): void
    {
        $admin = $this->buatAdmin();
        $wali = $this->buatWali('wali-hapus', 'Wali Hapus');
        $santri = $this->buatSantri('NIS-TETAP-ADA', 'Santri Tetap Ada', $wali);

        $this->actingAs($admin)
            ->delete(route('akun.destroy', $wali))
            ->assertRedirect(route('akun.index'));

        $this->assertSoftDeleted('users', ['id' => $wali->id]);
        $this->assertDatabaseHas('santris', [
            'id' => $santri->id,
            'nis' => 'NIS-TETAP-ADA',
            'wali_id' => null,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->delete(route('akun.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_account_creation_and_password_reset_routes_are_not_available(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->get('/akun/create')->assertMethodNotAllowed();
        $this->actingAs($admin)->post('/akun')->assertStatus(405);
        $this->actingAs($admin)->post('/akun/1/password')->assertNotFound();
        $this->assertFalse(Route::has('akun.create'));
        $this->assertFalse(Route::has('akun.store'));
        $this->assertFalse(Route::has('akun.password'));
    }

    public function test_wali_cannot_open_another_walis_santri(): void
    {
        $wali = $this->buatWali('wali-saya', 'Wali Saya');
        $santriSendiri = $this->buatSantri('NIS-SAYA', 'Anak Saya', $wali);
        $waliLain = $this->buatWali('wali-lain', 'Wali Lain');
        $santriWaliLain = $this->buatSantri('NIS-ORANG-LAIN', 'Anak Wali Lain', $waliLain);

        $this->actingAs($wali)
            ->get(route('santri.show', $santriSendiri))
            ->assertOk();

        $this->actingAs($wali)
            ->get(route('santri.show', $santriWaliLain))
            ->assertForbidden();
    }

    public function test_wali_can_log_in_without_email_verification(): void
    {
        $wali = $this->buatWali('wali-login', 'Wali Login');
        $this->buatSantri('NIS-WALI-LOGIN', 'Anak Wali Login', $wali);

        $this->post(route('login.attempt'), [
            'username' => $wali->username,
            'password' => 'wali-password',
        ])->assertRedirect(route('wali.dashboard'));

        $this->assertAuthenticatedAs($wali);
    }

    public function test_wali_and_santri_cannot_access_admin_account_or_dropdown_routes(): void
    {
        $wali = $this->buatWali('wali-non-admin', 'Wali Non Admin');
        $santri = $this->buatSantri('NIS-NON-ADMIN', 'Santri Non Admin');
        $akunSantri = User::create([
            'name' => $santri->nama,
            'username' => $santri->nis,
            'password' => Hash::make('santri-password'),
            'role' => User::ROLE_SANTRI,
            'santri_id' => $santri->id,
            'is_active' => true,
        ]);

        $this->actingAs($wali)->get(route('akun.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($wali)->get(route('santri.create'))->assertRedirect(route('dashboard'));
        $this->actingAs($akunSantri)->get(route('akun.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($akunSantri)->get(route('santri.create'))->assertRedirect(route('dashboard'));
    }

    public function test_public_wali_registration_routes_are_unavailable(): void
    {
        $this->get('/daftar')->assertNotFound();
        $this->post('/daftar')->assertNotFound();
        $this->get('/verifikasi')->assertNotFound();
    }

    public function test_root_route_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    private function buatAdmin(): User
    {
        return User::create([
            'name' => 'Admin Pengujian',
            'username' => 'admin-test',
            'password' => Hash::make('admin-password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function buatWali(string $username, string $name, string $password = 'wali-password'): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $username.'@example.test',
            'email_verified_at' => null,
            'password' => Hash::make($password),
            'role' => User::ROLE_WALI,
            'is_active' => true,
        ]);
    }

    private function buatSantri(string $nis, string $nama, ?User $wali = null): Santri
    {
        $santri = Santri::create([
            'nis' => $nis,
            'nama' => $nama,
            'jenis_kelamin' => 'Putra',
            'status_aktif' => 'Aktif',
        ]);

        if ($wali) {
            $wali->tautkanSantri($santri->id);
        }

        return $santri;
    }

    private function dataSantri(array $override = []): array
    {
        return array_merge([
            'nis' => 'NIS-DEFAULT',
            'nama' => 'Santri Pengujian',
            'jenis_kelamin' => 'Putra',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => 'Asatidzah',
        ], $override);
    }
}
