<?php declare(strict_types=1);

namespace PHPUnit\Event\Runtime;

use function sprintf;


final class Runtime
{
    private readonly OperatingSystem $operatingSystem;
    private readonly PHP $php;
    private readonly PHPUnit $phpunit;

    public function __construct()
    {
        $this->operatingSystem = new OperatingSystem;
        $this->php             = new PHP;
        $this->phpunit         = new PHPUnit;
    }

    public function asString(): string
    {
        $php = $this->php();

        return sprintf(
            'PHPUnit %s using PHP %s (%s) on %s',
            $this->phpunit()->versionId(),
            $php->version(),
            $php->sapi(),
            $this->operatingSystem()->operatingSystem(),
        );
    }

    public function operatingSystem(): OperatingSystem
    {
        return $this->operatingSystem;
    }

    public function php(): PHP
    {
        return $this->php;
    }

    public function phpunit(): PHPUnit
    {
        return $this->phpunit;
    }
}
