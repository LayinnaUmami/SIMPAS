<?php

namespace App\Models;

class Ratiban extends BaseActivity
{
    protected $table = 'ratibans';

    public static function typeKey(): string
    {
        return 'ratiban';
    }
}
