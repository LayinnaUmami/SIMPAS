<?php

namespace App\Models;

class Murojaah extends BaseActivity
{
    protected $table = 'murojaahs';

    public static function typeKey(): string
    {
        return 'murojaah';
    }
}
