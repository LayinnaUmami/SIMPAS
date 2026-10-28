<?php

namespace App\Models;

class Jamaah extends BaseActivity
{
    protected $table = 'jamaahs';

    public static function typeKey(): string
    {
        return 'jamaah';
    }
}
