<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;


interface StubInternal extends Stub
{
    public static function __phpunit_initConfigurableMethods(ConfigurableMethod ...$configurableMethods): void;

    public function __phpunit_getInvocationHandler(): InvocationHandler;

    public function __phpunit_setReturnValueGeneration(bool $returnValueGeneration): void;

    public function __phpunit_unsetInvocationMocker(): void;
}
