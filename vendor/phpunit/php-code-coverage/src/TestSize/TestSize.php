<?php declare(strict_types=1);

namespace SebastianBergmann\CodeCoverage\Test\TestSize;


abstract class TestSize
{
    public static function unknown(): self
    {
        return new Unknown;
    }

    public static function small(): self
    {
        return new Small;
    }

    public static function medium(): self
    {
        return new Medium;
    }

    public static function large(): self
    {
        return new Large;
    }

    
    public function isKnown(): bool
    {
        return false;
    }

    
    public function isUnknown(): bool
    {
        return false;
    }

    
    public function isSmall(): bool
    {
        return false;
    }

    
    public function isMedium(): bool
    {
        return false;
    }

    
    public function isLarge(): bool
    {
        return false;
    }

    abstract public function asString(): string;
}
