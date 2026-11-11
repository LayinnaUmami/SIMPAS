<?php

namespace Database\Seeders;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2ESeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin E2E',
            'username' => 'e2e-admin',
            'password' => 'E2E-Admin-Test-2026',
            'role' => User::ROLE_ADMIN,
            'email' => 'admin-e2e@example.test',
            'is_active' => true,
        ]);

        $waliMobile = User::create([
            'name' => 'Wali Mobile E2E',
            'username' => 'e2e-wali-mobile',
            'password' => 'E2E-Wali-Test-2026',
            'role' => User::ROLE_WALI,
            'email' => 'wali-mobile-e2e@example.test',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $anakMobilePertama = $this->buatSantri('E2E-MOB-001', 'Anak Mobile Pertama');
        $anakMobileKedua = $this->buatSantri('E2E-MOB-002', 'Anak Mobile Kedua');
        $anakMobilePertama->forceFill(['wali_id' => $waliMobile->id])->save();
        $anakMobileKedua->forceFill(['wali_id' => $waliMobile->id])->save();
        $waliMobile->forceFill(['santri_id' => $anakMobilePertama->id])->saveQuietly();

        $waliLain = User::create([
            'name' => 'Wali Lain E2E',
            'username' => 'e2e-wali-lain',
            'password' => 'E2E-Wali-Lain-2026',
            'role' => User::ROLE_WALI,
            'email' => 'wali-lain-e2e@example.test',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $anakWaliLain = $this->buatSantri('E2E-OTHER-001', 'Anak Wali Lain');
        $anakWaliLain->forceFill(['wali_id' => $waliLain->id])->save();
        $waliLain->forceFill(['santri_id' => $anakWaliLain->id])->saveQuietly();
    }

    private function buatSantri(string $nis, string $nama): Santri
    {
        return Santri::create([
            'nis' => $nis,
            'nama' => $nama,
            'jenis_kelamin' => 'Putri',
            'status_aktif' => 'Aktif',
            'jenis_diniyah' => 'Diniyah',
            'level_diniyah' => '1 Wustho',
        ]);
    }
}
