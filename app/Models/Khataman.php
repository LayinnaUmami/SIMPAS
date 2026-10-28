<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Khataman extends Model
{
    use SoftDeletes;

    /** Nama eksplisit: pluralisasi Eloquent menghasilkan "khatamen". */
    protected $table = 'khatamans';

    protected $guarded = [];

    protected $casts = [
        'tahun' => 'integer',
    ];

    public function santri()
    {
        return $this->belongsTo(Santri::class)->withTrashed();
    }
}
