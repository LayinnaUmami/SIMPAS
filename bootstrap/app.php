<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'wali' => \App\Http\Middleware\EnsureWali::class,
            'aktif' => EnsureAccountActive::class,
        ]);

        // Halaman login untuk seluruh role
        $middleware->redirectGuestsTo('/login');

        /*
         * Pemeriksaan status akun dijalankan pada SETIAP permintaan yang butuh
         * sesi, bukan hanya saat login. AuthController sudah menolak akun
         * nonaktif sekali, tetapi admin bisa menonaktifkan akun ketika sesi orang
         * itu masih hidup. Tanpa middleware ini, pengguna itu tetap punya akses
         * sampai ia logout sendiri.
         */
        $middleware->web(append: [
            EnsureAccountActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sesi habis/CSRF kedaluwarsa: arahkan balik dengan pesan Bahasa Indonesia.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                return redirect()->back()
                    ->withInput()
                    ->with('toast', [
                        'type' => 'error',
                        'message' => 'Sesi Anda telah berakhir. Silakan coba lagi.',
                    ]);
            }
        });
    })->create();
