<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak permintaan dari akun yang sudah dinonaktifkan.
 *
 * Kenapa perlu lapis kedua, padahal AuthController sudah memeriksa status akun:
 * pemeriksaan di login hanya berjalan SEKALI. Bila admin menonaktifkan akun
 * sementara sesi pengguna masih hidup, middleware inilah yang menutup aksesnya
 * pada permintaan berikutnya, bukan menunggunya sampai pengguna logout sendiri.
 *
 * Berbeda dengan EnsureAdmin, middleware ini tidak memilih arah: pemanggil yang
 * memutuskan halaman mana yang pantas, karena tujuan "halaman wali" versus
 * "halaman dashboard" tidak selalu sama untuk semua peran.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isActive()) {
            // Putuskan sesi lebih dulu. Kalau tidak, pengguna hanya diarahkan ke
            // login, masuk lagi, ditolak lagi, dan seterusnya.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Hubungi Admin/Pengurus.',
            ]);
        }

        return $next($request);
    }
}
