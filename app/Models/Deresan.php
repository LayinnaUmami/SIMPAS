<?php

namespace App\Models;

class Deresan extends BaseActivity
{
    protected $table = 'deresans';

    public static function typeKey(): string
    {
        return 'deresan';
    }
}
