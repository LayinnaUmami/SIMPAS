<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pelanggaran extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'poin' => 'integer',
        'is_auto' => 'boolean',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function scopePadaPeriode($q, string $mulai, string $akhir)
    {
        return $q->whereBetween('tanggal', [$mulai, $akhir]);
    }

    public function scopeCariSantri($q, ?string $keyword)
    {
        if (!$keyword) {
            return $q;
        }

        return $q->whereHas('santri', function ($sub) use ($keyword) {
            $sub->where('nama', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%");
        });
    }
}
