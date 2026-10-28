<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\User;
use App\Services\PendaftaranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran mandiri dengan verifikasi kode OTP lewat email.
 *
 * Batas yang ditegakkan di server:
 *
 * - Tidak ada pilihan peran. Akun pendaftaran SELALU wali, apa pun isian form.
 * - Email harus unik dan belum dipakai akun lain; email yang sudah ada
 *   lain tidak bisa didaftarkan ulang.
 * - Nomor WhatsApp dinormalisasi dan dicocokkan dengan no_hp Santri. Cocok
 *   berarti akun langsung tertaut ke data anak; tidak cocok berarti akun tetap
 *   dibuat tetapi tanpa relasi.
 * - Kode hanya bisa dipakai sekali, punya masa berlaku, dan jumlah tebakannya
 *   dibatasi.
 * - Pengiriman ulang dibatasi per alamat dan per alamat IP.
 */
class PendaftaranController extends Controller
{
    public function __construct(private PendaftaranService $pendaftaran)
    {
    }

    /* ------------------------------------------------------------------ */
    /* Formulir                                                            */
    /* ------------------------------------------------------------------ */

    public function form()
    {
        $this->pastikanAktif();

        return view('auth.register');
    }

    /* ------------------------------------------------------------------ */
    /* Mengirim kode                                                       */
    /* ------------------------------------------------------------------ */

    public function kirimKode(Request $request)
    {
        $this->pastikanAktif();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'telepon' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'telepon.required' => 'Nomor WhatsApp wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'password.min' => 'Password minimal 8 karakter.',
        ], [
            'name' => 'nama lengkap',
            'email' => 'email',
            'telepon' => 'nomor WhatsApp',
            'password' => 'password',
        ]);

        $email = strtolower(trim($data['email']));
        $telepon = $this->bersihkanTelepon($data['telepon']);

        // Email yang sudah dipakai akun lain tidak boleh didaftarkan ulang.
        // Pesannya sengaja tidak menyebutkan "email sudah terdaftar" untuk akun
        // yang belum terverifikasi, karena itu informasi yang belum final.
        $emailTerpakai = User::withTrashed()->where('email', $email)->exists();
        if ($emailTerpakai) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar. Silakan masuk atau hubungi Admin/Pengurus.',
            ]);
        }

        /*
         * Penjaga: hanya orang yang SUDAH dikenal sistem yang boleh mendaftar.
         *
         * Yang dianggap "dikenal":
         *  - email milik akun yang sudah ada (misalnya wali yang didaftarkan
         *    Admin tapi belum pernah login), atau
         *  - nomor WhatsApp yang cocok dengan no_hp seorang Santri.
         *
         * Kalau tidak cocok, pendaftaran ditolak. Pesannya sama untuk kedua
         * sebab: penyerang tidak bisa membedakan "email tidak ada" dari "nomor
         * tidak ada" dengan mencoba satu per satu.
         *
         * Set SIMPAS_REGISTRATION_REQUIRE_EXISTING=false di .env kalau Anda
         * memang ingin pendaftaran terbuka untuk semua orang.
         */
        if (config('simpas.registration.require_existing_contact', true)) {
            $emailDikenal = User::withTrashed()
                ->where('email', $email)
                ->exists();

            $nomorDikenal = Santri::query()
                ->whereNotNull('no_hp')
                ->get()
                ->contains(function (Santri $s) use ($telepon) {
                    return $this->pendaftaran->normalkanNomor($s->no_hp) ===
                        $this->pendaftaran->normalkanNomor($telepon);
                });

            if (! $emailDikenal && ! $nomorDikenal) {
                Log::warning('pendaftaran ditolak, kontak tidak dikenal', [
                    'email' => $email,
                    'telepon' => $telepon,
                    'ip' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'telepon' => 'Data Anda belum terdaftar di pondok. Hubungi Admin/Pengurus.',
                ]);
            }
        }

        $kunci = 'daftar:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, (int) config('simpas.registration.otp.max_resend', 3))) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan kode. Silakan coba lagi nanti.',
            ])->status(429);
        }

        [$kode, $akun, $anakCocok] = $this->pendaftaran->terbitkanKode(
            $email,
            trim($data['name']),
            $telepon,
            $request->ip()
        );

        RateLimiter::hit($kunci, 3600);

        // Password sudah diisi pemohon, jadi langsung dipasang. Akun belum bisa
        // masuk sebelum kode diverifikasi karena email_verified_at masih kosong
        // dan proses login memeriksanya.
        $akun->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
        ])->saveQuietly();

        Log::info('permintaan kode pendaftaran', [
            'user_id' => $akun->id,
            'email' => $email,
            'telepon_normal' => $this->pendaftaran->normalkanNomor($telepon),
            'jumlah_anak_tertaut' => $anakCocok->count(),
            'santri_tertaut' => $anakCocok->pluck('id')->all(),
            'ip' => $request->ip(),
        ]);

        $request->session()->put('daftar_email', $email);
        $request->session()->put('daftar_anak', $anakCocok->pluck('id')->all());

        /*
         * Mode pengembangan: MAIL_MAILER=log berarti email tidak benar-benar
         * terkirim, melainkan ditulis ke storage/logs/laravel.log. Kode karena itu
         * ikut dikembalikan ke layar supaya alur bisa dicoba tanpa SMTP. Setting
         * ini diambil dari mailer-nya, bukan dari kode: begitu MAIL_MAILER=smtp,
         * blok ini otomatis berhenti bekerja.
         */
        $modePengembangan = config('mail.default') === 'log';

        return redirect()
            ->route('daftar.verifikasi')
            ->with('kode_dev', $modePengembangan ? $kode : null)
            ->with('jumlah_anak', $anakCocok->count())
            ->with('toast', [
                'type' => $anakCocok->isEmpty() ? 'error' : 'success',
                'message' => $anakCocok->isEmpty()
                    ? 'Nomor tersebut belum terdaftar sebagai nomor wali. Hubungi Admin/Pengurus.'
                    : ($modePengembangan
                        ? 'Kode verifikasi dibuat. Mode pengembangan: mailer masih "log".'
                        : 'Kode verifikasi telah dikirim ke email Anda.'),
            ]);
    }

    /* ------------------------------------------------------------------ */
    /* Verifikasi                                                         */
    /* ------------------------------------------------------------------ */

    public function formVerifikasi(Request $request)
    {
        $this->pastikanAktif();

        $anakIds = (array) $request->session()->get('daftar_anak', []);
        $anak = $anakIds === [] ? collect() : Santri::whereIn('id', $anakIds)->orderBy('nis')->get();

        return view('auth.verify', [
            'email' => (string) $request->session()->get('daftar_email', ''),
            'anak' => $anak,
            'jumlahAnak' => $anak->count(),
        ]);
    }

    public function verifikasi(Request $request)
    {
        $this->pastikanAktif();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'kode' => ['required', 'string', 'max:10'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'kode.required' => 'Kode verifikasi wajib diisi.',
        ], [
            'email' => 'email',
            'kode' => 'kode verifikasi',
        ]);

        $email = strtolower(trim($data['email']));

        try {
            $akun = $this->pendaftaran->verifikasiKode($email, trim($data['kode']), $request->ip());
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['kode' => $e->getMessage()]);
        }

        if ($this->pendaftaran->perluPersetujuanAdmin()) {
            return redirect()->route('login')->with('toast', [
                'type' => 'success',
                'message' => 'Email terverifikasi. Akun Anda menunggu diaktifkan Admin/Pengurus.',
            ]);
        }

        $jumlahAnak = $akun->santris()->count();

        return redirect()->route('login')->with('toast', [
            'type' => 'success',
            'message' => $jumlahAnak > 0
                ? "Email terverifikasi. {$jumlahAnak} anak terhubung. Silakan masuk dengan akun Anda."
                : 'Email terverifikasi. Silakan masuk, lalu hubungi Admin/Pengurus untuk menghubungkan data anak.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Kirim ulang                                                        */
    /* ------------------------------------------------------------------ */

    public function kirimUlang(Request $request)
    {
        $this->pastikanAktif();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ], [
            'email' => 'email',
        ]);

        $email = strtolower(trim($data['email']));

        $kunci = 'daftar:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($kunci, (int) config('simpas.registration.otp.max_resend', 3))) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan kode. Silakan coba lagi nanti.',
            ])->status(429);
        }

        $akun = User::where('email', $email)->first();

        if (! $akun) {
            // Jangan bocorkan apakah email itu terdaftar.
            return redirect()->route('daftar.verifikasi')->with('toast', [
                'type' => 'success',
                'message' => 'Jika email tersebut terdaftar, kode baru sudah dikirim.',
            ]);
        }

        [$kode, , $anakCocok] = $this->pendaftaran->terbitkanKode(
            $email,
            $akun->name,
            $akun->telepon,
            $request->ip()
        );

        RateLimiter::hit($kunci, 3600);

        $modePengembangan = config('mail.default') === 'log';

        return redirect()->route('daftar.verifikasi')->with([
            'daftar_email' => $email,
            'daftar_anak' => $anakCocok->pluck('id')->all(),
            'kode_dev' => $modePengembangan ? $kode : null,
            'jumlah_anak' => $anakCocok->count(),
            'toast' => [
                'type' => 'success',
                'message' => $modePengembangan
                    ? 'Kode baru dibuat (mode pengembangan).'
                    : 'Kode baru telah dikirim ke email Anda.',
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Pembantu                                                           */
    /* ------------------------------------------------------------------ */

    private function pastikanAktif(): void
    {
        abort_unless($this->pendaftaran->aktif(), 404);
    }

    /** Membuang semua karakter kecuali angka dan tanda plus di depan. */
    private function bersihkanTelepon(string $telepon): string
    {
        $telepon = trim($telepon);
        $plus = Str::startsWith($telepon, '+') ? '+' : '';
        $angka = preg_replace('/\D+/', '', $telepon);

        return $plus . ($angka ?? '');
    }
}
