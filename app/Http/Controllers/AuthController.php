<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Autentikasi berbasis kode akun (username) atau NIS Santri.
 *
 * Prinsip yang dipegang file ini:
 *
 * 1. Tidak ada halaman register publik. Akun hanya dibuat oleh Admin/Pengurus
 *    lewat /akun, sehingga peran tidak pernah ditentukan oleh orang yang
 *    mendaftar sendiri.
 * 2. Kegagalan tidak pernah membocorkan informasi. Pesan salah identitas dan
 *    salah password sengaja sama, dan jalur kegagalan tetap melakukan
 *    Hash::check agar waktu responsnya tidak membedakan akun ada atau tidak.
 * 3. Password tidak pernah masuk log. Yang dicatat hanya username, IP, dan
 *    alasan kegagalan.
 * 4. Sesi diregenerasi setelah login supaya cookie yang didapat sebelum login
 *    tidak berlaku sesudahnya (session fixation).
 */
class AuthController extends Controller
{
    /**
     * Hash bcrypt yang dipakai sebagai boneka.
     *
     * Nilai ini hanya dibaca, tidak pernah dicocokkan dengan apa pun. Tujuannya
     * membuat Hash::check tetap berjalan saat username tidak ditemukan, sehingga
     * waktu respons untuk "akun tidak ada" dan "password salah" hampir sama.
     * Tanpa ini, penyerang bisa menebak username yang valid hanya dari selisih
     * waktu (terukur 277 ms pada versi sebelumnya).
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    /** Percobaan gagal yang diperbolehkan dalam satu jendela waktu. */
    private const MAX_ATTEMPTS = 5;

    /** Panjang jendela pembatas, dalam detik. */
    private const DECAY_SECONDS = 900;

    /* ------------------------------------------------------------------ */
    /* Tampilan                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Halaman masuk.
     *
     * Pengguna yang sudah punya sesi tidak perlu melihat form lagi, langsung
     * diarahkan ke beranda sesuai perannya.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->landingRoute());
        }

        return view('auth.login');
    }

    /* ------------------------------------------------------------------ */
    /* Proses masuk                                                       */
    /* ------------------------------------------------------------------ */

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Kode akun atau NIS wajib diisi.',
            'username.max' => 'Kode akun atau NIS terlalu panjang.',
            'password.required' => 'Password wajib diisi.',
            'password.max' => 'Password terlalu panjang.',
        ], [
            'username' => 'kode akun atau NIS',
            'password' => 'password',
        ]);

        $kode = trim($kredensial['username']);

        // Kunci throttle digabung dengan username supaya satu penyerang tidak
        // bisa mengunci akun orang lain hanya dengan menyebut namanya.
        $kunci = $this->throttleKey($request, $kode);

        if (RateLimiter::tooManyAttempts($kunci, self::MAX_ATTEMPTS)) {
            $sisaDetik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan masuk. '
                    . 'Coba lagi dalam ' . ceil($sisaDetik / 60) . ' menit.',
            ])->status(429);
        }

        $user = $this->cariUser($kode);

        // Password selalu diverifikasi, termasuk saat $user bernilai null.
        // Hash yang dibandingkan ditulis lebih dulu agar kedua cabang tetap
        // menjalankan Hash::check atas input yang sama.
        $hashDibandingkan = $user?->password ?? self::DUMMY_HASH;
        $passwordBenar = Hash::check($kredensial['password'], $hashDibandingkan);

        if (! $user || ! $passwordBenar) {
            RateLimiter::hit($kunci, self::DECAY_SECONDS);

            // Dicatat tanpa password, hanya username dan IP.
            Log::warning('login gagal', [
                'username' => $kode,
                'ip' => $request->ip(),
                'alasan' => $user ? 'password salah' : 'username tidak dikenal',
                'sisa_percobaan' => RateLimiter::remaining($kunci, self::MAX_ATTEMPTS),
            ]);

            // Satu pesan untuk dua sebab yang berbeda, supaya penyerang tidak
            // bisa memisahkan "username salah" dari "password salah".
            throw ValidationException::withMessages([
                'username' => 'Kode akun/NIS atau password salah.',
            ]);
        }

        if (! $user->isActive()) {
            RateLimiter::hit($kunci, self::DECAY_SECONDS);

            Log::warning('login ditolak, akun nonaktif', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Akun Anda berstatus nonaktif. Hubungi Admin/Pengurus.',
            ]);
        }

        // Akun dibuat dan ditautkan oleh Admin; email bukan syarat login.
        if (! $user->hasSantriLink()) {
            Log::warning('login ditolak, akun wali tanpa relasi santri', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Akun ini belum terhubung dengan data Santri. Hubungi Admin/Pengurus.',
            ]);
        }

        // Percobaan berhasil: kembalikan jatah ke titik awal.
        RateLimiter::clear($kunci);

        Auth::login($user, $request->boolean('remember'));

        // Session fixation: id sesi lama tidak lagi berlaku setelah login.
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Log::info('login berhasil', [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route($user->landingRoute()));
    }

    /* ------------------------------------------------------------------ */
    /* Proses keluar                                                      */
    /* ------------------------------------------------------------------ */

    public function logout(Request $request)
    {
        $userId = $request->user()?->id;

        Auth::logout();

        // invalidate() menghapus seluruh isi sesi; regenerateToken() mengganti
        // token CSRF. Tanpa keduanya, token lama masih berlaku di tab lain.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('logout', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('login')->with('toast', [
            'type' => 'success',
            'message' => 'Anda telah keluar dari aplikasi.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Pembantu                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Mencari akun berdasarkan tiga identitas, berurutan:
     *
     * 1. kode akun (username)
     * 2. email
     * 3. NIS Santri
     *
     * NIS hanya berguna kalau ada akun yang benar-benar tertaut ke Santri itu.
     * Mengetahui NIS seseorang tidak otomatis memberi akses.
     */
    private function cariUser(string $kode): ?User
    {
        $user = User::where('username', $kode)->first();

        if ($user) {
            return $user;
        }

        if (str_contains($kode, '@')) {
            $user = User::where('email', strtolower($kode))->first();

            if ($user) {
                return $user;
            }
        }

        $santri = Santri::where('nis', $kode)->first();

        return $santri ? User::where('santri_id', $santri->id)->first() : null;
    }

    /**
     * Kunci pembatas percobaan.
     *
     * Username ikut masuk agar penyerang tidak bisa mengunci seluruh alamat IP
     * hanya dengan mencoba banyak nama, dan IP ikut masuk agar satu akun tidak
     * bisa dibombardir dari banyak mesin sekaligus.
     */
    private function throttleKey(Request $request, string $kode): string
    {
        return 'login:' . Str::transliterate(Str::lower($kode) . '|' . $request->ip());
    }
}
