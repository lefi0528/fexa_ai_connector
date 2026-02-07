<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;


final class Phpt extends Test
{
    
    public function isPhpt(): bool
    {
        return true;
    }

    
    public function id(): string
    {
        return $this->file();
    }

    
    public function name(): string
    {
        return $this->file();
    }
}
