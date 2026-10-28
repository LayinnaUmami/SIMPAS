<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('jenis');
            $table->enum('tingkat', ['Ringan', 'Sedang', 'Berat'])->default('Ringan');
            $table->text('keterangan')->nullable();
            $table->integer('poin')->default(-3); // bersifat negatif
            $table->boolean('is_auto')->default(false);
            // Kunci otomatisasi: mencegah pelanggaran otomatis dibuat berulang (bagian 20)
            $table->string('auto_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
    }
};
