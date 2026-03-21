<?php

declare(strict_types=1);

namespace Sburina\Whmcs\Facades;

use Illuminate\Support\Facades\Facade;

class Whmcs extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whmcs';
    }
}
