<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Tabel aktivitas (bagian 9 - 16)
|--------------------------------------------------------------------------
| Semua tabel memiliki struktur seragam:
|   santri_id + tanggal + sesi  =>  UNIK (aturan anti-duplikasi bagian 17)
|
| Jamaah    : 5 sesi dalam satu tanggal (Subuh..Isya), status Hadir/Izin/Alfa
| Deresan   : 2 sesi (Ba'da Subuh, Ba'da Ashar)
| Lainnya   : 1 sesi per tanggal (nilai sesi tetap disimpan agar logika seragam)
| Diniyah   : poin terpisah, TIDAK masuk ranking, TIDAK otomatis pelanggaran
*/

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('simpas.activities') as $key => $cfg) {
            Schema::create($cfg['table'], function (Blueprint $table) use ($key, $cfg) {
                $table->id();
                $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('sesi');
                $table->enum('status', $cfg['statuses']);
                $table->integer('poin')->default(0);
                $table->timestamps();
                $table->softDeletes();

                // ★ Anti-duplikasi: 1 santri + 1 tanggal + 1 sesi = 1 data
                $table->unique(['santri_id', 'tanggal', 'sesi'], "uniq_{$cfg['table']}_santri_tanggal_sesi");
                $table->index(['tanggal', 'status']);
            });
        }
    }

    public function down(): void
    {
        foreach (config('simpas.activities') as $cfg) {
            Schema::dropIfExists($cfg['table']);
        }
    }
};
