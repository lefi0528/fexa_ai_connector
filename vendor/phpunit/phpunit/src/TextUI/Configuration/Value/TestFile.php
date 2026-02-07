<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use PHPUnit\Util\VersionComparisonOperator;


final class TestFile
{
    private readonly string $path;
    private readonly string $phpVersion;
    private readonly VersionComparisonOperator $phpVersionOperator;

    public function __construct(string $path, string $phpVersion, VersionComparisonOperator $phpVersionOperator)
    {
        $this->path               = $path;
        $this->phpVersion         = $phpVersion;
        $this->phpVersionOperator = $phpVersionOperator;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function phpVersion(): string
    {
        return $this->phpVersion;
    }

    public function phpVersionOperator(): VersionComparisonOperator
    {
        return $this->phpVersionOperator;
    }
}
