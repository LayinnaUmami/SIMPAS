<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom users.santri_id hanya menampung SATU anak per akun. Data di pondok
 * menunjukkan satu orang tua punya beberapa anak dengan satu nomor HP, bahkan
 * ada satu nomor yang dipakai enam Santri. Dengan satu kolom, anak-anak
 * kedua dan seterusnya tidak akan pernah terlihat oleh walinya.
 *
 * Yang dijaga migrasi ini:
 * - TIDAK ADA data lama yang dihapus. Kolom users.santri_id tetap ada dan tetap
 *   diisi, dibaca sebagai "anak utama" supaya seluruh kode dan halaman yang sudah
 *   berjalan tidak ikut rusak.
 * - Semua relasi lama DISALINKAN ke tabel baru lewat penyisipan di bawah, jadi
 *   30 wali yang sudah punya anak tetap punya anak.
 * - is_primary menandai anak yang jadi anak utama; nilainya selalu paling banyak
 *   satu per wali.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guardian_santri')) {
            Schema::create('guardian_santri', function (Blueprint $table) {
                $table->id();

                // Akun wali. Tidak sengaja dibatasi ke users.id saja karena
                // admin juga boleh punya relasi bila nanti dibutuhkan.
                $table->unsignedBigInteger('user_id');

                $table->unsignedBigInteger('santri_id');

                // Hubungan keluarga dengan wali: Ayah, Ibu, Paman, Nenek, dll.
                $table->string('hubungan', 40)->default('Wali');

                // Anak utama. Tepat satu per wali; sisanya false.
                $table->boolean('is_primary')->default(false);

                $table->timestamps();

                // Satu anak tidak boleh Appear dua kali dalam akun yang sama.
                $table->unique(['user_id', 'santri_id'], 'guardian_santri_user_santri_unique');

                $table->index(['user_id', 'is_primary'], 'guardian_santri_primary_index');
                $table->index('santri_id', 'guardian_santri_santri_index');

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('santri_id')->references('id')->on('santris')->cascadeOnDelete();
            });
        }

        /*
         * Pindahkan relasi lama ke tabel baru.
         *
         * Disalin apa adanya: setiap akun wali/santri yang punya strolls_id
         * menjadi satu baris guardian_santri dengan is_primary = true. Baris
         * yang sudah ada (misalnya dari instalasi sebelumnya) dilewati agar
         * migrasi tidak gagal karena bentrok.
         */
        DB::table('users')
            ->whereNotNull('santri_id')
            ->where('role', '!=', 'admin')
            ->orderBy('id')
            ->each(function ($user) {
                $sudahAda = DB::table('guardian_santri')
                    ->where('user_id', $user->id)
                    ->where('santri_id', $user->santri_id)
                    ->exists();

                if ($sudahAda) {
                    return;
                }

                DB::table('guardian_santri')->insert([
                    'user_id' => $user->id,
                    'santri_id' => $user->santri_id,
                    'hubungan' => 'Wali',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_santri');
    }
};
