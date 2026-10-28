<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // guardian_santri dipertahankan sebagai data historis; wali_id menjadi relasi aktif.
        $assignments = collect();

        if (Schema::hasTable('guardian_santri')) {
            $assignments = DB::table('guardian_santri as guardian')
                ->join('users', 'users.id', '=', 'guardian.user_id')
                ->where('users.role', 'wali')
                ->get(['guardian.santri_id', 'guardian.user_id']);
        }

        $legacyPrimaryLinks = DB::table('users')
            ->where('role', 'wali')
            ->whereNotNull('santri_id')
            ->get(['santri_id', 'id'])
            ->map(fn ($user) => (object) [
                'santri_id' => $user->santri_id,
                'user_id' => $user->id,
            ]);

        $ownersBySantri = [];

        foreach ($assignments->concat($legacyPrimaryLinks) as $assignment) {
            $santriId = (int) $assignment->santri_id;
            $userId = (int) $assignment->user_id;

            if (isset($ownersBySantri[$santriId]) && $ownersBySantri[$santriId] !== $userId) {
                throw new RuntimeException(
                    "Santri ID {$santriId} terhubung ke lebih dari satu akun wali. "
                    .'Selesaikan konflik pada guardian_santri/users.santri_id sebelum menjalankan migration ini.'
                );
            }

            $ownersBySantri[$santriId] = $userId;
        }

        if (! Schema::hasColumn('santris', 'wali_id')) {
            Schema::table('santris', function (Blueprint $table) {
                $table->foreignId('wali_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        foreach ($ownersBySantri as $santriId => $userId) {
            $santri = DB::table('santris')->where('id', $santriId)->first(['wali_id']);

            if ($santri && $santri->wali_id !== null && (int) $santri->wali_id !== $userId) {
                throw new RuntimeException(
                    "Santri ID {$santriId} sudah memiliki wali berbeda pada kolom wali_id."
                );
            }

            DB::table('santris')
                ->where('id', $santriId)
                ->whereNull('wali_id')
                ->update(['wali_id' => $userId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('santris', 'wali_id')) {
            Schema::table('santris', function (Blueprint $table) {
                $table->dropConstrainedForeignId('wali_id');
            });
        }
    }
};
