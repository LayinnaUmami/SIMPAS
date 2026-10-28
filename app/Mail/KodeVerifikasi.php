<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email berisi kode verifikasi pendaftaran.
 *
 * Isi email sengaja dibuat pendek dan tanpa data pribadi apa pun: hanya nama,
 * kode, dan kapan kedaluwarsanya. Tidak ada NIS, nomor HP, atau data anak yang
 * ikut dikirim, supaya email yang salah tujuan tidak membocorkan apa pun.
 */
class KodeVerifikasi extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $nama,
        public string $kode,
        public $kedaluwarsa,
    ) {
    }

    public function build()
    {
        $menit = max(1, now()->diffInMinutes($this->kedaluwarsa));

        return $this->subject('Kode verifikasi akun ' . config('simpas.nama_aplikasi'))
            ->view('mail.kode-verifikasi')
            ->with([
                'nama' => $this->nama,
                'kode' => $this->kode,
                'menit' => $menit,
                'namaAplikasi' => config('simpas.nama_aplikasi'),
            ]);
    }
}
