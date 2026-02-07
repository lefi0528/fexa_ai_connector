<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject\Rule;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Invocation as BaseInvocation;


interface ParametersRule
{
    
    public function apply(BaseInvocation $invocation): void;

    public function verify(): void;
}
