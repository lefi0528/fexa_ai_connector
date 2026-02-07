<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;


trait StubApi
{
    
    private static array $__phpunit_configurableMethods;
    private bool $__phpunit_returnValueGeneration          = true;
    private ?InvocationHandler $__phpunit_invocationMocker = null;

    
    public static function __phpunit_initConfigurableMethods(ConfigurableMethod ...$configurableMethods): void
    {
        static::$__phpunit_configurableMethods = $configurableMethods;
    }

    
    public function __phpunit_setReturnValueGeneration(bool $returnValueGeneration): void
    {
        $this->__phpunit_returnValueGeneration = $returnValueGeneration;
    }

    
    public function __phpunit_getInvocationHandler(): InvocationHandler
    {
        if ($this->__phpunit_invocationMocker === null) {
            $this->__phpunit_invocationMocker = new InvocationHandler(
                static::$__phpunit_configurableMethods,
                $this->__phpunit_returnValueGeneration,
            );
        }

        return $this->__phpunit_invocationMocker;
    }

    
    public function __phpunit_unsetInvocationMocker(): void
    {
        $this->__phpunit_invocationMocker = null;
    }
}
