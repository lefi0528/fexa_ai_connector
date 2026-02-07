<?php declare(strict_types=1);

namespace PHPUnit\Metadata\Api;

use function array_unshift;
use function assert;
use function class_exists;
use PHPUnit\Metadata\Parser\Registry;
use PHPUnit\Util\Reflection;
use ReflectionClass;


final class HookMethods
{
    
    private static array $hookMethods = [];

    
    public function hookMethods(string $className): array
    {
        if (!class_exists($className)) {
            return self::emptyHookMethodsArray();
        }

        if (isset(self::$hookMethods[$className])) {
            return self::$hookMethods[$className];
        }

        self::$hookMethods[$className] = self::emptyHookMethodsArray();

        foreach (Reflection::methodsInTestClass(new ReflectionClass($className)) as $method) {
            $methodName = $method->getName();

            assert(!empty($methodName));

            $metadata = Registry::parser()->forMethod($className, $methodName);

            if ($method->isStatic()) {
                if ($metadata->isBeforeClass()->isNotEmpty()) {
                    array_unshift(
                        self::$hookMethods[$className]['beforeClass'],
                        $methodName,
                    );
                }

                if ($metadata->isAfterClass()->isNotEmpty()) {
                    self::$hookMethods[$className]['afterClass'][] = $methodName;
                }
            }

            if ($metadata->isBefore()->isNotEmpty()) {
                array_unshift(
                    self::$hookMethods[$className]['before'],
                    $methodName,
                );
            }

            if ($metadata->isPreCondition()->isNotEmpty()) {
                array_unshift(
                    self::$hookMethods[$className]['preCondition'],
                    $methodName,
                );
            }

            if ($metadata->isPostCondition()->isNotEmpty()) {
                self::$hookMethods[$className]['postCondition'][] = $methodName;
            }

            if ($metadata->isAfter()->isNotEmpty()) {
                self::$hookMethods[$className]['after'][] = $methodName;
            }
        }

        return self::$hookMethods[$className];
    }

    
    private function emptyHookMethodsArray(): array
    {
        return [
            'beforeClass'   => ['setUpBeforeClass'],
            'before'        => ['setUp'],
            'preCondition'  => ['assertPreConditions'],
            'postCondition' => ['assertPostConditions'],
            'after'         => ['tearDown'],
            'afterClass'    => ['tearDownAfterClass'],
        ];
    }
}
