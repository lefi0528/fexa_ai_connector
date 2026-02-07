<?php declare(strict_types=1);

namespace PHPUnit\Event\TestData;


final class DataFromTestDependency extends TestData
{
    public static function from(string $data): self
    {
        return new self($data);
    }

    
    public function isFromTestDependency(): bool
    {
        return true;
    }
}
