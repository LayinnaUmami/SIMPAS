<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Artisan;

putenv('APP_ENV=e2e');

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.e2e');

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
    $pdoWithNow = function () use ($pdo) {
        $connection = $pdo();
        $connection->sqliteCreateFunction('now', fn () => now()->toDateTimeString());

        return $connection;
    };

    return new SQLiteConnection($pdoWithNow, $database, $prefix, $config);
});

$exitCode = Artisan::call('migrate:fresh', [
    '--seed' => true,
    '--seeder' => 'Database\\Seeders\\E2ESeeder',
    '--force' => true,
]);

fwrite(STDOUT, Artisan::output());

exit($exitCode);
