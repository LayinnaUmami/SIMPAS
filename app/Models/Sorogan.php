<?php

namespace App\Models;

class Sorogan extends BaseActivity
{
    protected $table = 'sorogans';

    public static function typeKey(): string
    {
        return 'sorogan';
    }
}
