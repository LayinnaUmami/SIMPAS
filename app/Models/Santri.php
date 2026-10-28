<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Santri extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'jenis_kelamin' => 'string',
    ];

    /* ------------------------------------------------------------------ */
    /* Relasi                                                             */
    /* ------------------------------------------------------------------ */

    public function khatamans()
    {
        return $this->hasMany(Khataman::class)->orderByDesc('tahun');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'santri_id');
    }

    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_id');
    }

    public function prestasi()
    {
        return $this->hasMany(Prestasi::class);
    }

    public function pelanggaran()
    {
        return $this->hasMany(Pelanggaran::class);
    }

    /** Relasi aktivitas dinamis berdasar key config (jamaah, pengajian, ...). */
    public function aktivitas(string $type)
    {
        $model = config("simpas.activities.{$type}.model");

        return $this->hasMany($model);
    }

    /* ------------------------------------------------------------------ */
    /* Aksesor                                                            */
    /* ------------------------------------------------------------------ */

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->foto) {
            return null;
        }

        return str_starts_with($this->foto, 'http') || str_starts_with($this->foto, '/')
            ? $this->foto
            : asset('storage/' . $this->foto);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->nama));

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }

    public function getLevelDiniyahTampilAttribute(): string
    {
        if ($this->jenis_diniyah === 'Diniyah') {
            return (string) $this->level_diniyah;
        }

        // Asatidzah / Mutimin tidak menampilkan level (bagian 5)
        return '-';
    }

    public function scopeAktif($q)
    {
        return $q->where('status_aktif', 'Aktif');
    }

    public function scopeCari($q, ?string $keyword)
    {
        if (!$keyword) {
            return $q;
        }

        return $q->where(function ($sub) use ($keyword) {
            $sub->where('nama', 'like', "%{$keyword}%")
                ->orWhere('nis', 'like', "%{$keyword}%");
        });
    }
}
