<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Verifikasi email saat pendaftaran mandiri.
 *
 * Dua hal ditambah, tidak ada yang dihapus:
 *
 * 1. Tabel account_verifications menyimpan satu kode OTP yang belum dipakai untuk
 *    satu alamat email. Kode disimpan sebagai hash, bukan teks biasa: kalau
 *    basis data dibocorkan, kode yang bocor tidak langsung bisa dipakai.
 *
 * 2. Kolom users.email_verified_at menandai email mana yang sudah dibuktikan
 *    pemiliknya lewat kode. Kolom ini tetap nullable supaya 35 akun lama yang
 *    email-nya kosong tidak ikut tersentuh.
 *
 * Catatan penting soal indeks unik pada email: MySQL mengizinkan banyak NULL
 * satu indeks unik, jadi 35 akun tanpa email tidak saling bertabrakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_verifications')) {
            Schema::create('account_verifications', function (Blueprint $table) {
                $table->id();

                // Email tujuan kode. Panjang 190 mengikuti batas InnoDB untuk
                // indeks dengan utf8mb4 (191 karakter x 4 byte, dibulatkan).
                $table->string('email', 190);

                // Hash dari kode, bukan kodenya. Verifikasi dilakukan dengan
                // Hash::check sehingga isi tabel tidak berguna bila bocor.
                $table->string('code_hash', 60);

                // Berapa kali kode sudah dicoba, untuk membatasi tebakan.
                $table->unsignedTinyInteger('attempts')->default(0);

                $table->timestamp('expires_at');

                // Terisi setelah kode berhasil dipakai, supaya kode lama tidak
                // bisa dipakai dua kali.
                $table->timestamp('consumed_at')->nullable();

                $table->string('ip', 45)->nullable();

                $table->timestamps();

                $table->index(['email', 'consumed_at'], 'account_verifications_email_open_index');
                $table->index('expires_at', 'account_verifications_expires_index');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });

        /*
         * Email harus unik supaya login bisa memakai email sebagai identitas dan
         * supaya tidak ada dua akun memakai alamat yang sama.
         *
         * Hanya ditambahkan bila belum ada indeks unik pada email. Data sekarang
         * hanya punya satu email terisi, jadi aman.
         */
        $sudahAda = false;
        foreach (Schema::getIndexes('users') as $index) {
            if ($index['unique'] && $index['columns'] === ['email']) {
                $sudahAda = true;
                break;
            }
        }

        if (! $sudahAda) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email', 'users_email_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });

        Schema::dropIfExists('account_verifications');
    }
};
