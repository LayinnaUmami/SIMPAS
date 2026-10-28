<?php

namespace App\Services;

use App\Mail\KodeVerifikasi;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Pendaftaran mandiri dengan verifikasi kode OTP melalui email.
 *
 * Prinsip yang dipegang:
 *
 * 1. Kode disimpan sebagai hash. Isi tabel account_verifications tidak berguna
 *    bagi penyerang meskipun basis datanya bocor, karena yang tersimpan bukan
 *    kodenya melainkan hash-nya.
 * 2. Satu alamat email hanya boleh punya satu kode yang masih hidup. Meminta
 *    kode baru membatalkan kode lama, jadi kode lama tidak bisa dipakai belakangan.
 * 3. Batas tebakan dan batas kirim ulang ditegakkan di server, bukan di
 *    peramban.
 * 4. Peran tidak pernah datang dari pemohon. Akun pendaftaran selalu wali.
 */
class PendaftaranService
{
    /* ------------------------------------------------------------------ */
    /* Pengaturan                                                          */
    /* ------------------------------------------------------------------ */

    private function pengaturan(string $kunci, mixed $bawaan = null): mixed
    {
        return config("simpas.registration.otp.{$kunci}", $bawaan);
    }

    public function aktif(): bool
    {
        return (bool) config('simpas.registration.enabled', true);
    }

    public function perluPersetujuanAdmin(): bool
    {
        return (bool) config('simpas.registration.require_admin_approval', false);
    }

    /* ------------------------------------------------------------------ */
    /* Penerbitan kode                                                     */
    /* ------------------------------------------------------------------ */

    /**
     * Membuat kode OTP baru untuk satu email, lalu menautkan semua Santri yang
     * nomornya cocok.
     *
     * Mengembalikan kode dalam bentuk TEKS POLOS hanya supaya bisa ditampilkan
     * pada mode pengembangan. Kode yang disimpan di basis data adalah hash-nya.
     *
     * @return array{0: string, 1: User, 2: \Illuminate\Support\Collection}
     */
    public function terbitkanKode(string $email, string $nama, ?string $telepon, ?string $alamatIp): array
    {
        $panjang = (int) $this->pengaturan('length', 6);

        // Kode angka tanpa nol di depan, supaya tidak terbaca "012345" saat
        // diketik ulang. Panjang dijaga agar tetap tepat.
        do {
            $kode = (string) random_int(0, (10 ** $panjang) - 1);
        } while (strlen($kode) < $panjang);

        $kedaluwarsa = now()->addMinutes((int) $this->pengaturan('ttl', 15));

        $akun = DB::transaction(function () use ($email, $nama, $telepon, $alamatIp, $kode, $kedaluwarsa) {
            // Kode lama untuk email yang sama dibatalkan lebih dulu, supaya
            // hanya ada satu kode yang berlaku pada satu waktu.
            DB::table('account_verifications')
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            $akunTersimpan = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    // Kode akun harus ada dan unik. Dibuat dari email agar pemohon
                    // tidak perlu memilih, dan tidak pernah bentrok dengan akun lama.
                    'username' => $this->kodeAkunUnik($email),
                    'password' => Str::random(40), // sementara, ditimpa saat verifikasi
                    'role' => config('simpas.registration.default_role', User::ROLE_WALI),
                    'telepon' => $telepon,
                    'is_active' => ! $this->perluPersetujuanAdmin(),
                    // Anak utama diisi nanti lewat tautanAnak(); di sini sengaja
                    // dikosongkan supaya tidak menunjuk satu anak saja.
                    'santri_id' => null,
                ]
            );

            /*
             * Tautkan SEMUA Santri yang nomornya cocok, bukan hanya yang pertama.
             *
             * Satu orang tua bisa punya beberapa anak dengan satu nomor HP, dan
             * di pondok ada nomor yang dipakai enam Santri. Hanya menautkan satu
             * akan membuat anak-anak yang lain tidak pernah terlihat.
             *
             * Anak pertama yang cocok otomatis menjadi anak utama; sisanya
             * ditautkan sebagai anak tambahan.
             */
            $anakCocok = $this->cariSantriDariTelepon($telepon);

            foreach ($anakCocok as $santri) {
                $akunTersimpan->tautkanSantri((int) $santri->id, 'Wali');
            }

            return [$akunTersimpan, $anakCocok];
        });

        DB::table('account_verifications')->insert([
            'email' => $email,
            'code_hash' => Hash::make($kode),
            'attempts' => 0,
            'expires_at' => $kedaluwarsa,
            'ip' => $alamatIp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->kirimKode($email, $nama, $kode, $kedaluwarsa);

        return [$kode, $akun[0], $akun[1]];
    }

    /**
     * Mengirim kode lewat email.
     *
     * Dengan MAIL_MAILER=log, kode ditulis ke storage/logs/laravel.log
     * alih-alih dikirim. Itu disengaja untuk lingkungan sekarang: tidak ada
     * SMTP yang dikonfigurasi. Pindah ke pengiriman sungguhan cukup mengubah
     * MAIL_MAILER=smtp beserta kredensialnya, tanpa menyentuh kode ini.
     */
    private function kirimKode(string $email, string $nama, string $kode, $kedaluwarsa): void
    {
        try {
            Mail::to($email)->send(new KodeVerifikasi($nama, $kode, $kedaluwarsa));
        } catch (\Throwable $e) {
            // Kegagalan email tidak boleh diam-diam. Dicatat tanpa isi kode.
            Log::error('gagal mengirim kode verifikasi', [
                'email' => $email,
                'penyebab' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Kode verifikasi gagal dikirim. Silakan coba beberapa saat lagi.',
                0,
                $e
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Verifikasi                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Memeriksa kode yang diketik pengguna.
     *
     * Mengembalikan akun bila benar, atau melempar ValidationException dengan
     * pesan Bahasa Indonesia bila salah, kedaluwarsa, atau sudah dipakai.
     */
    public function verifikasiKode(string $email, string $kode, ?string $alamatIp): User
    {
        $catatan = DB::table('account_verifications')
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (! $catatan) {
            throw new \RuntimeException('Kode verifikasi tidak ditemukan. Silakan minta kode baru.');
        }

        if (now()->greaterThan($catatan->expires_at)) {
            DB::table('account_verifications')->where('id', $catatan->id)
                ->update(['consumed_at' => now()]);

            throw new \RuntimeException('Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.');
        }

        if ($catatan->attempts >= (int) $this->pengaturan('max_attempts', 5)) {
            throw new \RuntimeException('Terlalu banyak percobaan kode. Silakan minta kode baru.');
        }

        // Panjang kode tidak diperiksa terpisah. Hash::check sudah menolak kode
        // dengan panjang berbeda, dan kode yang gagal tetap dihitung sebagai
        // satu percobaan, sehingga penyerang tidak bisa mendapat informasi
        // gratis hanya dengan mengirim input panjang sembarang.
        if (! Hash::check($kode, $catatan->code_hash)) {
            DB::table('account_verifications')->where('id', $catatan->id)
                ->increment('attempts');

            $sisa = (int) $this->pengaturan('max_attempts', 5) - ($catatan->attempts + 1);

            Log::warning('kode verifikasi salah', [
                'email' => $email,
                'ip' => $alamatIp,
                'sisa_percobaan' => $sisa,
            ]);

            throw new \RuntimeException(
                $sisa > 0
                    ? "Kode verifikasi salah. Sisa percobaan: {$sisa}."
                    : 'Kode verifikasi salah dan sudah habis percobaannya. Silakan minta kode baru.'
            );
        }

        $akun = User::where('email', $email)->firstOrFail();

        DB::transaction(function () use ($catatan, $akun) {
            DB::table('account_verifications')->where('id', $catatan->id)
                ->update(['consumed_at' => now()]);

            $akun->forceFill([
                'email_verified_at' => now(),
                'password_changed_at' => now(),
            ])->saveQuietly();
        });

        Log::info('email terverifikasi', [
            'user_id' => $akun->id,
            'email' => $email,
            'santri_id' => $akun->santri_id,
            'perlu_persetujuan' => $this->perluPersetujuanAdmin(),
        ]);

        return $akun;
    }

    /* ------------------------------------------------------------------ */
    /* Pembantu                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Mencari SEMUA Santri yang nomor_hp-nya sama dengan nomor yang diisi pemohon.
     *
     * Satu orang tua bisa punya beberapa anak dengan satu nomor yang sama. Data di
     * pondok bahkan menunjukkan satu nomor dipakai enam Santri. Karena itu
     * pencarian mengembalikan daftar, bukan satu hasil.
     *
     * Nomor dinormalisasi ke format 62xxxxxxxxx: awalan 0, 62, dan +62 dibuang
     * supaya "0812...", "62812...", dan "+62812..." dianggap nomor yang sama.
     *
     * Mengetahui nomor ini tidak memberi akses ke data anak mana pun sebelum
     * akun aktif dan tautannya diperiksa.
     *
     * @return \Illuminate\Support\Collection<int, Santri>
     */
    public function cariSantriDariTelepon(?string $telepon)
    {
        if (! $telepon) {
            return collect();
        }

        $normal = $this->normalkanNomor($telepon);

        if ($normal === null) {
            return collect();
        }

        return Santri::query()
            ->whereNotNull('no_hp')
            ->get()
            ->filter(function (Santri $s) use ($normal) {
                return $this->normalkanNomor($s->no_hp) === $normal;
            })
            ->sortBy('nis')
            ->values();
    }

    /**
     * Mengubah nomor telepon menjadi format 62xxxxxxxxx.
     */
    public function normalkanNomor(string $nomor): ?string
    {
        $angka = preg_replace('/\D+/', '', $nomor);

        if ($angka === null || $angka === '') {
            return null;
        }

        if (str_starts_with($angka, '62')) {
            $angka = substr($angka, 2);
        } elseif (str_starts_with($angka, '0')) {
            $angka = substr($angka, 1);
        }

        // Nomor Indonesia setelah kode negara 62 punya 9 sampai 12 digit.
        if (strlen($angka) < 9 || strlen($angka) > 12) {
            return null;
        }

        return '62' . $angka;
    }

    /**
     * Membuat kode akun unik dari email.
     *
     * Dipakai supaya pemohon tidak perlu memilih kode akun dan tidak mungkin
     * bentrok dengan kode yang sudah dipakai. Bila email menghasilkan kode yang
     * sudah ada, angka ditambahkan sampai unik.
     */
    private function kodeAkunUnik(string $email): string
    {
        $dasar = Str::lower(preg_replace('/[^A-Za-z0-9._-]/', '-', explode('@', $email)[0]));
        $dasar = trim($dasar, '.-_') ?: 'wali';
        $dasar = Str::limit($dasar, 100, '');

        $kandidat = $dasar;
        $nomor = 1;

        while (User::withTrashed()->where('username', $kandidat)->exists()) {
            $nomor++;
            $kandidat = Str::limit($dasar, 100 - strlen((string) $nomor) - 1, '') . $nomor;
        }

        return $kandidat;
    }
}
