<?php declare(strict_types=1);

namespace PHPUnit\Framework;


final class ActualValueIsNotAnObjectException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'Actual value is not an object',
        );
    }
}
