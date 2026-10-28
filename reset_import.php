<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Santri;
use App\Models\User;
use App\Models\Khataman;
use App\Models\Prestasi;
use App\Models\Pelanggaran;
use App\Models\Jamaah;
use App\Models\Pengajian;
use App\Models\Deresan;
use App\Models\Sorogan;
use App\Models\Murojaah;
use App\Models\Ratiban;
use App\Models\Diniyah;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== RESET SEMUA DATA ===\n";

// Disable foreign key checks
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Truncate all tables in correct order
$tables = [
    'jamaahs', 'pengajians', 'deresans', 'sorogans', 'murojaahs', 'ratibans', 'diniyahs',
    'prestasis', 'pelanggarans', 'khatamans',
    'santris', 'users',
    'password_reset_tokens', 'sessions', 'jobs', 'cache', 'cache_locks',
];

foreach ($tables as $table) {
    try {
        DB::table($table)->truncate();
        echo "Truncated: $table\n";
    } catch (\Exception $e) {
        echo "Skip $table: " . $e->getMessage() . "\n";
    }
}

// Re-enable foreign key checks
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\n=== IMPORT EXCEL ===\n";

$excelPath = 'C:/Users/asus/Downloads/SANTRI_RQ_SESUAI_TEMPLATE.xlsx';

if (!file_exists($excelPath)) {
    die("File tidak ditemukan: $excelPath\n");
}

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelPath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

echo "Total baris Excel: " . count($rows) . "\n";

// Cari header row
$headerRow = null;
foreach ($rows as $i => $row) {
    $rowStr = implode(' ', array_map('strtolower', $row));
    if (str_contains($rowStr, 'nis') || str_contains($rowStr, 'nama')) {
        $headerRow = $i;
        echo "Header ditemukan di baris: " . ($i + 1) . "\n";
        echo "Header: " . implode(' | ', $row) . "\n";
        break;
    }
}

if ($headerRow === null) {
    die("Header tidak ditemukan\n");
}

// Mapping kolom berdasarkan header
$headers = $rows[$headerRow];
$colMap = [];
foreach ($headers as $idx => $h) {
    $h = strtolower(trim((string)$h));
    if ($h === 'nis') $colMap['nis'] = $idx;
    elseif ($h === 'nama') $colMap['nama'] = $idx;
    elseif (str_contains($h, 'jk') || str_contains($h, 'kelamin')) $colMap['jenis_kelamin'] = $idx;
    elseif ($h === 'alamat') $colMap['alamat'] = $idx;
    elseif ($h === 'kamar') $colMap['kamar'] = $idx;
    elseif (str_contains($h, 'status')) $colMap['status_aktif'] = $idx;
    elseif (str_contains($h, 'pendidikan')) $colMap['pendidikan_formal'] = $idx;
    elseif (str_contains($h, 'diniyah') && !str_contains($h, 'level')) $colMap['jenis_diniyah'] = $idx;
    elseif (str_contains($h, 'level')) $colMap['level_diniyah'] = $idx;
    elseif (str_contains($h, 'ayah') && !str_contains($h, 'pekerjaan')) $colMap['nama_ayah'] = $idx;
    elseif (str_contains($h, 'pekerjaan') && str_contains($h, 'ayah')) $colMap['pekerjaan_ayah'] = $idx;
    elseif (str_contains($h, 'ibu') && !str_contains($h, 'pekerjaan')) $colMap['nama_ibu'] = $idx;
    elseif (str_contains($h, 'pekerjaan') && str_contains($h, 'ibu')) $colMap['pekerjaan_ibu'] = $idx;
    elseif (str_contains($h, 'wali') && !str_contains($h, 'pekerjaan') && !str_contains($h, 'alamat')) $colMap['nama_wali'] = $idx;
    elseif (str_contains($h, 'pekerjaan') && str_contains($h, 'wali')) $colMap['pekerjaan_wali'] = $idx;
    elseif (str_contains($h, 'hp') || str_contains($h, 'telepon') || str_contains($h, 'no_')) $colMap['no_hp'] = $idx;
    elseif (str_contains($h, 'alamat') && str_contains($h, 'wali')) $colMap['alamat_wali'] = $idx;
}

echo "Mapping kolom:\n";
foreach ($colMap as $field => $idx) {
    echo "  $field -> kolom $idx\n";
}

// Import data
$imported = 0;
for ($i = $headerRow + 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    if (empty(array_filter($row))) continue;

    $nis = trim((string)($row[$colMap['nis']] ?? ''));
    $nama = trim((string)($row[$colMap['nama']] ?? ''));
    
    if (!$nis || !$nama) continue;

    $data = [
        'nis' => $nis,
        'nama' => $nama,
        'jenis_kelamin' => in_array(trim((string)($row[$colMap['jenis_kelamin']] ?? '')), ['Putra', 'Putri']) ? trim((string)$row[$colMap['jenis_kelamin']]) : 'Putri',
        'alamat' => trim((string)($row[$colMap['alamat']] ?? '')) ?: null,
        'kamar' => trim((string)($row[$colMap['kamar']] ?? '')) ?: null,
        'status_aktif' => in_array(trim((string)($row[$colMap['status_aktif']] ?? '')), ['Aktif', 'Alumni', 'Pindah', 'Keluar']) ? trim((string)$row[$colMap['status_aktif']]) : 'Aktif',
        'pendidikan_formal' => trim((string)($row[$colMap['pendidikan_formal']] ?? '')) ?: null,
        'jenis_diniyah' => in_array(trim((string)($row[$colMap['jenis_diniyah']] ?? '')), ['Diniyah', 'Asatidzah', 'Mutimin']) ? trim((string)$row[$colMap['jenis_diniyah']]) : 'Diniyah',
        'level_diniyah' => trim((string)($row[$colMap['level_diniyah']] ?? '')) ?: null,
        'nama_ayah' => trim((string)($row[$colMap['nama_ayah']] ?? '')) ?: null,
        'pekerjaan_ayah' => trim((string)($row[$colMap['pekerjaan_ayah']] ?? '')) ?: null,
        'nama_ibu' => trim((string)($row[$colMap['nama_ibu']] ?? '')) ?: null,
        'pekerjaan_ibu' => trim((string)($row[$colMap['pekerjaan_ibu']] ?? '')) ?: null,
        'nama_wali' => trim((string)($row[$colMap['nama_wali']] ?? '')) ?: null,
        'pekerjaan_wali' => trim((string)($row[$colMap['pekerjaan_wali']] ?? '')) ?: null,
        'no_hp' => trim((string)($row[$colMap['no_hp']] ?? '')) ?: null,
        'alamat_wali' => trim((string)($row[$colMap['alamat_wali']] ?? '')) ?: null,
    ];

    $santri = Santri::create($data);
    $imported++;
    echo "$imported. $nis - $nama\n";
}

echo "\nTotal Santri diimport: $imported\n";

echo "\n=== BUAT ADMIN ===\n";
User::create([
    'name' => 'Admin',
    'username' => 'admin',
    'password' => Hash::make('admin123'),
    'role' => 'admin',
]);
echo "Admin: admin / admin123\n";

echo "\n=== BUAT WALI SANTRI UNTUK SETIAP SANTRI ===\n";
$waliCount = 0;
Santri::all()->each(function ($s) use (&$waliCount) {
    $username = 'wali' . str_pad($s->id, 3, '0', STR_PAD_LEFT);
    $password = $username . '123';
    
    User::create([
        'name' => 'Wali ' . $s->nama,
        'username' => $username,
        'password' => Hash::make($password),
        'role' => 'wali',
        'santri_id' => $s->id,
    ]);
    $waliCount++;
    echo "$username => {$s->nama} ({$s->nis})\n";
});

echo "\nTotal Wali Santri dibuat: $waliCount\n";

echo "\n=== SELESAI ===\n";
echo "Role yang ada: admin, wali (TIDAK ADA role santri)\n";
echo "Admin: admin / admin123\n";
echo "Wali format: wali{ID} / wali{ID}123 (contoh: wali001 / wali001123)\n";