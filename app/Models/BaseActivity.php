<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Kelas dasar seluruh aktivitas absensi (Jamaah, Pengajian, Deresan,
 * Sorogan, Murojaah, Ratiban, Diniyah).
 *
 * Struktur kolom seragam: santri_id + tanggal + sesi => UNIK.
 */
abstract class BaseActivity extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'tanggal' => 'date',
        'poin' => 'integer',
    ];

    /** Kunci config: 'jamaah', 'pengajian', ... */
    abstract public static function typeKey(): string;

    public static function cfg(): array
    {
        return config('simpas.activities.' . static::typeKey());
    }

    public static function label(): string
    {
        return static::cfg()['label'];
    }

    public static function icon(): string
    {
        return static::cfg()['icon'];
    }

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }

    /** Poin untuk satu status (bagian 10, 16). */
    public static function pointsFor(string $status): int
    {
        return (int) config("simpas.points.{$status}", 0);
    }

    /** Total poin untuk sekumpulan record. */
    public static function sumPoin($records): int
    {
        return (int) collect($records)->sum('poin');
    }

    public function scopePadaTanggal($q, string $tanggal)
    {
        return $q->where('tanggal', $tanggal);
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

    public function tanggalFormat(): string
    {
        return $this->tanggal?->translatedFormat('d M Y') ?? '-';
    }
}
