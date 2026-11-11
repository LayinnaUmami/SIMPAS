<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Santri;
use Illuminate\Contracts\Console\Kernel;

$s = Santri::where('nama', 'like', '%Anisa%')->first();
echo 'ID: '.$s->id.PHP_EOL;
echo 'Nama: '.$s->nama.PHP_EOL;
echo 'NIS: '.$s->nis.PHP_EOL;
echo 'Foto: '.$s->foto.PHP_EOL;
echo 'Foto URL: '.$s->foto_url.PHP_EOL;
echo 'Initials: '.$s->initials.PHP_EOL;
