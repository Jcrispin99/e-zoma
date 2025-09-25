<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Kardex extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'kardex';
    }
}
