<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject;


interface MockObjectInternal extends MockObject, StubInternal
{
    public function __phpunit_hasMatchers(): bool;

    public function __phpunit_setOriginalObject(object $originalObject): void;

    public function __phpunit_verify(bool $unsetInvocationMocker = true): void;
}
