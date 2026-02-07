<?php declare(strict_types=1);

namespace PHPUnit\Framework\MockObject\Builder;

use PHPUnit\Framework\MockObject\Stub\Stub as BaseStub;


interface Stub extends Identity
{
    
    public function will(BaseStub $stub): Identity;
}
