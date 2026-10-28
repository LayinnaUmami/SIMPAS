<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Timbulkan konfigurasi dari tabel settings (menu Pengaturan).
     */
    public function boot(): void
    {
        try {
            $points = Setting::get('points');
            if ($points) {
                config(['simpas.points' => json_decode($points, true) ?: config('simpas.points')]);
            }

            $enabled = Setting::get('auto_violation_enabled');
            if ($enabled !== null) {
                config(['simpas.auto_violation.enabled' => $enabled === '1']);
            }

            $nama = Setting::get('nama_aplikasi');
            if ($nama) {
                config(['simpas.nama_aplikasi' => $nama]);
            }
        } catch (\Throwable $e) {
            // Tabel settings belum termigrasi - abaikan
        }

        // Bahasa Indonesia untuk format tanggal & angka
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'indonesian');
        \Carbon\Carbon::setLocale(config('app.locale', 'id'));
    }
}
