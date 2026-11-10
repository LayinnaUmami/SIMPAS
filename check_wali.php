<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$users = User::where('role', 'wali')->get();
foreach ($users as $u) {
    echo $u->username.' | '.($u->santri ? $u->santri->nama : 'NO SANTRI').PHP_EOL;
}
