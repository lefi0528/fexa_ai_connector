<?php declare(strict_types=1);

namespace PHPUnit\Util;

use Throwable;


final class Cloner
{
    
    public static function clone(object $original): object
    {
        try {
            return clone $original;
        } catch (Throwable) {
            return $original;
        }
    }
}
