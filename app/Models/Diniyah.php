<?php

namespace App\Models;

/**
 * Diniyah - poin TIDAK masuk ranking, TIDAK masuk poin aktivitas,
 * TIDAK memicu otomatisasi pelanggaran (bagian 16 & 20).
 */
class Diniyah extends BaseActivity
{
    protected $table = 'diniyahs';

    public static function typeKey(): string
    {
        return 'diniyah';
    }
}
