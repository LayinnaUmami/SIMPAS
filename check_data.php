<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Santri;
use App\Models\User;
use App\Models\Prestasi;
use App\Models\Pelanggaran;
use App\Models\Khataman;

echo "=== SANTRI ===\n";
$count = Santri::count();
echo "Total Santri: $count\n";
foreach (Santri::orderBy('id')->get() as $s) {
    echo "  {$s->id} | {$s->nis} | {$s->nama} | {$s->kamar} | {$s->jenis_diniyah}\n";
}

echo "\n=== USER WALI ===\n";
$walis = User::where('role', 'wali')->get();
echo "Total Wali: " . $walis->count() . "\n";
foreach ($walis as $w) {
    echo "  {$w->username} | {$w->name} | santri_id: {$w->santri_id} | santri: " . ($w->santri ? $w->santri->nama : 'NULL') . "\n";
}

echo "\n=== PRESTASI ===\n";
echo "Total: " . Prestasi::count() . "\n";

echo "\n=== PELANGGARAN ===\n";
echo "Total: " . Pelanggaran::count() . "\n";

echo "\n=== KHATAMAN ===\n";
echo "Total: " . Khataman::count() . "\n";

echo "\n=== ACTIVITY SAMPLE ===\n";
foreach (config('simpas.activities') as $key => $cfg) {
    $count = $cfg['model']::count();
    echo "$key: $count\n";
}