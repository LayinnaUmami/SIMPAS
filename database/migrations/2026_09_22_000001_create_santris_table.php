<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | DATA SANTRI (hanya identitas - spesifikasi bagian 5 & 6)
        |----------------------------------------------------------------------
        | Tidak menyimpan aktivitas/prestasi/pelanggaran.
        */

        Schema::create('santris', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique();
            $table->string('nama');
            $table->enum('jenis_kelamin', ['Putra', 'Putri']);
            $table->text('alamat')->nullable();
            $table->string('kamar')->nullable();
            $table->string('foto')->nullable();

            // Status aktif
            $table->enum('status_aktif', ['Aktif', 'Alumni', 'Pindah', 'Keluar'])->default('Aktif');

            // Pendidikan formal
            $table->string('pendidikan_formal')->nullable();

            // Pendidikan diniyah
            $table->enum('jenis_diniyah', ['Diniyah', 'Asatidzah', 'Mutimin'])->default('Diniyah');
            // Level hanya relevan saat jenis_diniyah = Diniyah
            // (string biasa: nilai mengandung apostrof seperti "1 'Ulya")
            $table->string('level_diniyah', 20)->nullable();

            // Orang tua / wali (bagian 6) - hanya satu nomor HP
            $table->string('nama_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('nama_wali')->nullable();
            $table->string('pekerjaan_wali')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat_wali')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // FK users -> santris (dibuat setelah tabel santris tersedia)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('santri_id')->references('id')->on('santris')->nullOnDelete();
        });

        /*
        |----------------------------------------------------------------------
        | RIWAYAT KHATAMAN (bagian 7) - terpisah & dapat berkali-kali
        |----------------------------------------------------------------------
        */

        Schema::create('khatamans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->string('jenis_khatam');
            $table->year('tahun');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['santri_id', 'jenis_khatam', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khatamans');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['santri_id']);
        });
        Schema::dropIfExists('santris');
    }
};
