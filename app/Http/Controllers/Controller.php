<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Hanya Admin/Pengurus yang boleh mengubah data (bagian 3). */
    protected function requireAdmin(): void
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403, 'Maaf, akses hanya untuk Admin/Pengurus.');
        }
    }

    /** Santri/wali hanya boleh melihat data miliknya sendiri. */
    protected function scopeSantri($query)
    {
        $user = auth()->user();

        if ($user?->isWali()) {
            $query->whereIn('santri_id', $user->santris()->select('santris.id'));
        } elseif ($user?->isSantri()) {
            $query->where('santri_id', $user->santri_id);
        }

        return $query;
    }

    protected function authorizeSantri(Santri $santri): void
    {
        $user = auth()->user();

        abort_unless($user && $user->canViewSantri($santri), 403, 'Anda tidak dapat melihat data Santri ini.');
    }
}
