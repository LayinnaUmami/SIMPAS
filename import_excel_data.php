<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== IMPORT SANTRI ===\n";
$spreadsheet = IOFactory::load('C:/Users/asus/Downloads/SANTRI_RQ_SESUAI_TEMPLATE.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

// Skip header row
for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    if (empty($row[0])) continue;
    
    // Map Excel columns (0-indexed)
    // 0: nis, 1: nama, 2: jenis_kelamin, 3: alamat, 4: kamar, 5: status_aktif
    // 6: pendidikan_formal, 7: jenis_diniyah, 8: level_diniyah
    // 9: nama_ayah, 10: pekerjaan_ayah, 11: nama_ibu, 12: pekerjaan_ibu
    // 13: nama_wali, 14: pekerjaan_wali, 15: no_hp, 16: alamat_wali
    
    $jenisDiniyah = $row[7] ?? 'Diniyah';
    // Fix typos: "Diniah", "diniah" -> "Diniyah"
    $jenisDiniyah = strtolower($jenisDiniyah);
    if (in_array($jenisDiniyah, ['diniah', 'diniyah'])) $jenisDiniyah = 'Diniyah';
    elseif ($jenisDiniyah === 'asatidzah') $jenisDiniyah = 'Asatidzah';
    elseif ($jenisDiniyah === 'mutimin') $jenisDiniyah = 'Mutimin';
    else $jenisDiniyah = 'Diniyah';
    
    $data = [
        'nis' => $row[0],
        'nama' => $row[1],
        'jenis_kelamin' => $row[2],
        'alamat' => $row[3] ?: null,
        'kamar' => $row[4] ?: null,
        'status_aktif' => $row[5] ?? 'Aktif',
        'pendidikan_formal' => $row[6] ?: null,
        'jenis_diniyah' => $jenisDiniyah,
        'level_diniyah' => $row[8] ?: null,
        'nama_ayah' => $row[9] ?: null,
        'pekerjaan_ayah' => $row[10] ?: null,
        'nama_ibu' => $row[11] ?: null,
        'pekerjaan_ibu' => $row[12] ?: null,
        'nama_wali' => $row[13] ?: null,
        'pekerjaan_wali' => $row[14] ?: null,
        'no_hp' => $row[15] ?: null,
        'alamat_wali' => $row[16] ?: null,
    ];
    
    $santri = Santri::updateOrCreate(['nis' => $data['nis']], $data);
    echo "Imported: {$data['nis']} - {$data['nama']} (jenis_diniyah: {$data['jenis_diniyah']})\n";
}

echo "\n=== IMPORT WALI USERS ===\n";
$spreadsheet = IOFactory::load('C:/Users/asus/Downloads/daftar_user_password_roudlotul_quran.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    if (empty($row[1])) continue;
    
    $roleLabel = $row[0];
    $username = $row[1];
    $password = $row[2];
    $santriInfo = $row[3] ?? '';
    
    // Extract NIS from "RQ0001 - Achlis Chafiyyu"
    if (preg_match('/^(RQ\d+)\s*-\s*(.+)$/', $santriInfo, $m)) {
        $nis = $m[1];
        $santri = Santri::where('nis', $nis)->first();
        
        if ($santri) {
            if (strtolower($roleLabel) === 'admin') {
                $role = 'admin';
            } else {
                $role = 'wali';
            }
            
            User::updateOrCreate(['username' => $username], [
                'name' => $role === 'admin' ? 'Admin' : 'Wali ' . $santri->nama,
                'password' => Hash::make($password),
                'role' => $role,
                'santri_id' => $role === 'wali' ? $santri->id : null,
                'email' => $role === 'admin' ? 'admin@simpas.id' : null,
            ]);
            echo "Created: $username - {$santri->nama}\n";
        } else {
            echo "Santri not found for: $santriInfo\n";
        }
    }
}

echo "\n=== DONE ===\n";