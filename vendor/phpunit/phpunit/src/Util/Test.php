<?php declare(strict_types=1);

namespace PHPUnit\Util;

use function str_starts_with;
use PHPUnit\Metadata\Parser\Registry;
use ReflectionMethod;


final class Test
{
    public static function isTestMethod(ReflectionMethod $method): bool
    {
        if (!$method->isPublic()) {
            return false;
        }

        if (str_starts_with($method->getName(), 'test')) {
            return true;
        }

        $metadata = Registry::parser()->forMethod(
            $method->getDeclaringClass()->getName(),
            $method->getName(),
        );

        return $metadata->isTest()->isNotEmpty();
    }
}
