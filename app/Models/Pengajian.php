<?php

namespace App\Models;

class Pengajian extends BaseActivity
{
    protected $table = 'pengajians';

    public static function typeKey(): string
    {
        return 'pengajian';
    }
}
