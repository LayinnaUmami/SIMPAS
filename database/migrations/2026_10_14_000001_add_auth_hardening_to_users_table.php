<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penguatan autentikasi: status aktif, jejak login terakhir, dan waktu ganti sandi.
 *
 * Migrasi ini hanya MENAMBAH kolom. Tidak ada kolom yang dihapus, tidak ada baris
 * yang dihapus, dan tidak ada nilai lama yang ditimpa: is_active diisi TRUE untuk
 * seluruh akun yang sudah ada, sehingga perilaku aplikasi tidak berubah bagi
 * pengguna yang sekarang.
 *
 * Mengapa perlu:
 * - Tanpa is_active, akun yang sudah tidak berlaku (santri keluar, wali pindah)
 *   tetap bisa login karena tidak ada tempat untuk menandainya nonaktif.
 * - last_login_at membantu pengelola melihat akun terakhir yang dipakai.
 * - password_changed_at menjadi dasar jika nanti aplikasi mewajibkan penggantian
 *   sandi setelah kata sandi diatur ulang oleh admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                // TRUE = akun boleh login. Default TRUE supaya akun lama tetap bisa masuk.
                $table->boolean('is_active')->default(true)->after('role')->index('users_is_active_index');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['is_active', 'last_login_at', 'password_changed_at'],
                fn (string $column) => Schema::hasColumn('users', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
