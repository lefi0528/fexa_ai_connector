<?php declare(strict_types=1);

namespace PHPUnit\Framework;

use function sprintf;


final class ComparisonMethodDoesNotAcceptParameterTypeException extends Exception
{
    public function __construct(string $className, string $methodName, string $type)
    {
        parent::__construct(
            sprintf(
                '%s is not an accepted argument type for comparison method %s::%s().',
                $type,
                $className,
                $methodName,
            ),
        );
    }
}
