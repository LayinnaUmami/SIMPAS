<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya Admin/Pengurus yang boleh mengubah data.
 * Santri/Wali bersifat hanya-baca (bagian 3).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
            }

            // Jangan pakai back() buta: tanpa header Referer, url()->previous()
            // menunjuk ke route yang sama dan memicu loop redirect 302.
            // Referer juga bisa menunjuk route tamu (/login) sehingga pesan hilang
            // di tengah rantai redirect — pakai hanya bila masih satu origin.
            $sebelumnya = $request->headers->get('referer');
            $satuOrigin = $sebelumnya
                && rtrim($sebelumnya, '/') !== rtrim($request->fullUrl(), '/')
                && str_starts_with(rtrim($sebelumnya, '/'), rtrim(url('/'), '/'));

            return redirect()->to($satuOrigin ? $sebelumnya : route('dashboard'))
                ->withErrors(['access' => 'Maaf, akses hanya untuk Admin/Pengurus.']);
        }

        return $next($request);
    }
}
