<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya Wali dan Santri yang boleh mengakses halaman "/wali/*".
 *
 * Kenapa Santri boleh: controller wali membatasi akun Santri ke profilnya
 * sendiri, sedangkan akun Wali dibatasi ke anak yang memiliki wali_id tersebut.
 *
 * Admin justru dilarang: halaman /wali tidakWdibuka untuk admin karena akan
 * membingungkan, dan admin sudah punya dashboard sendiri yang lengkap.
 *
 * Penting: peran Santri harus ikut diterima di sini. Kalau tidak, Santri yang
 * diarahkan ke /wali/dashboard akan tertendang balik ke /dashboard, dan
 * /dashboard akan mengembalikannya lagi: putaran redirect tanpa akhir.
 */
class EnsureWali
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isWaliOrSantri()) {
            return $next($request);
        }

        if ($user && $user->isAdmin()) {
            return redirect()->route('dashboard')
                ->withErrors(['access' => 'Halaman ini hanya untuk Wali dan Santri.']);
        }

        // Tidak login: halaman di bawah /wali memang berada di dalam grup
        // middleware auth, jadi middleware auth yang mengarahkan ke /login.
        abort_unless($user, 403, 'Akses ditolak.');

        return $next($request);
    }
}
