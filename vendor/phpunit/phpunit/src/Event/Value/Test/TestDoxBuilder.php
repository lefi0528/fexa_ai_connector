<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;

use PHPUnit\Event\TestData\MoreThanOneDataSetFromDataProviderException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Logging\TestDox\NamePrettifier;


final class TestDoxBuilder
{
    
    public static function fromTestCase(TestCase $testCase): TestDox
    {
        $prettifier = new NamePrettifier;

        return new TestDox(
            $prettifier->prettifyTestClassName($testCase::class),
            $prettifier->prettifyTestCase($testCase, false),
            $prettifier->prettifyTestCase($testCase, true),
        );
    }

    
    public static function fromClassNameAndMethodName(string $className, string $methodName): TestDox
    {
        $prettifier = new NamePrettifier;

        $prettifiedMethodName = $prettifier->prettifyTestMethodName($methodName);

        return new TestDox(
            $prettifier->prettifyTestClassName($className),
            $prettifiedMethodName,
            $prettifiedMethodName,
        );
    }
}
