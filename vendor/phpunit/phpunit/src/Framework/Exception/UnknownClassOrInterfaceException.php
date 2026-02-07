<?php declare(strict_types=1);

namespace PHPUnit\Framework;

use function sprintf;


final class UnknownClassOrInterfaceException extends InvalidArgumentException
{
    public function __construct(string $name)
    {
        parent::__construct(
            sprintf(
                'Class or interface "%s" does not exist',
                $name,
            ),
        );
    }
}
