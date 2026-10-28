<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('jenis');            // Akademik, Al-Qur'an, Keagamaan, Olahraga, Seni, Organisasi, Lainnya
            $table->string('nama_prestasi');
            $table->string('tingkat');          // Pondok .. Internasional
            $table->string('penyelenggara')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('poin')->default(0); // bersifat positif
            $table->string('bukti')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasis');
    }
};
