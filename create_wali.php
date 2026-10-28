<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Santri;
use Illuminate\Support\Facades\Hash;

Santri::all()->each(function ($s) {
    if (!$s->user && !User::where('username', 'wali' . str_pad($s->id, 3, '0', STR_PAD_LEFT))->exists()) {
        $username = 'wali' . str_pad($s->id, 3, '0', STR_PAD_LEFT);
        $password = $username . '123';
        User::create([
            'name' => 'Wali ' . $s->nama,
            'username' => $username,
            'password' => Hash::make($password),
            'role' => 'wali',
            'santri_id' => $s->id,
        ]);
        echo $username . ' => ' . $s->nama . ' (' . $s->nis . ')' . PHP_EOL;
    }
});