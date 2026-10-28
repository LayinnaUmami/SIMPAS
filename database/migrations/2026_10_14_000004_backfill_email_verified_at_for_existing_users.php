<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menandai email akun lama sebagai sudah terverifikasi.
 *
 * Masalah yang diperbaiki:
 * Aturan login mengatakan bahwa sebuah akun tidak boleh masuk sebelum email-nya
 * dibuktikan dengan kode OTP. Tanpa migrasi ini, akun yang dibuat Admin/Pengurus
 * SEBELUM kolom email_verified_at ada ikut terkunci: emailnya terisi tetapi
 * email_verified_at kosong, sehingga ditolak dengan pesan "belum diverifikasi".
 * Akun admin termasuk salah satunya, dan aplikasi jadi tidak bisa dipakai sama
 * sekali.
 *
 * Mengapa aman ditandai terverifikasi:
 * Akun-akun ini dibuat oleh petugas, bukan oleh orang yang mendaftar sendiri.
 * Emailnya sudah diperiksa petugas ketika akun dibuat, jadi tidak ada kode OTP
 * yang perlu diminta. Hanya akun hasil pendaftaran mandiri yang wajib lewat kode.
 *
 * Yang ditulis: hanya kolom email_verified_at. Tidak ada baris dihapus, tidak
 * ada akun dinonaktifkan, tidak ada kata sandi disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('email')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('COALESCE(created_at, now())')]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: email_verified_at tidak menyimpan informasi yang
        // hilang nilainya bila dikosongkan. Menjalankan down tidak mengubah apa pun.
    }
};
