<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestasi extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'poin' => 'integer',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    public function getBuktiUrlAttribute(): ?string
    {
        if (!$this->bukti) {
            return null;
        }

        return str_starts_with($this->bukti, 'http') || str_starts_with($this->bukti, '/')
            ? $this->bukti
            : asset('storage/' . $this->bukti);
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
