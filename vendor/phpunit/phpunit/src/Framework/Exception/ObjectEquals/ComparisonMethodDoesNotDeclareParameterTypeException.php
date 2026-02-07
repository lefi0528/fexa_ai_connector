<?php declare(strict_types=1);

namespace PHPUnit\Framework;

use function sprintf;


final class ComparisonMethodDoesNotDeclareParameterTypeException extends Exception
{
    public function __construct(string $className, string $methodName)
    {
        parent::__construct(
            sprintf(
                'Parameter of comparison method %s::%s() does not have a declared type.',
                $className,
                $methodName,
            ),
        );
    }
}
