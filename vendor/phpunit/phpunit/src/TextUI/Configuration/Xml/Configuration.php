<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use PHPUnit\TextUI\Configuration\ExtensionBootstrapCollection;
use PHPUnit\TextUI\Configuration\Php;
use PHPUnit\TextUI\Configuration\Source;
use PHPUnit\TextUI\Configuration\TestSuiteCollection;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\CodeCoverage;
use PHPUnit\TextUI\XmlConfiguration\Logging\Logging;


abstract class Configuration
{
    private readonly ExtensionBootstrapCollection $extensions;
    private readonly Source $source;
    private readonly CodeCoverage $codeCoverage;
    private readonly Groups $groups;
    private readonly Logging $logging;
    private readonly Php $php;
    private readonly PHPUnit $phpunit;
    private readonly TestSuiteCollection $testSuite;

    public function __construct(ExtensionBootstrapCollection $extensions, Source $source, CodeCoverage $codeCoverage, Groups $groups, Logging $logging, Php $php, PHPUnit $phpunit, TestSuiteCollection $testSuite)
    {
        $this->extensions   = $extensions;
        $this->source       = $source;
        $this->codeCoverage = $codeCoverage;
        $this->groups       = $groups;
        $this->logging      = $logging;
        $this->php          = $php;
        $this->phpunit      = $phpunit;
        $this->testSuite    = $testSuite;
    }

    public function extensions(): ExtensionBootstrapCollection
    {
        return $this->extensions;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function codeCoverage(): CodeCoverage
    {
        return $this->codeCoverage;
    }

    public function groups(): Groups
    {
        return $this->groups;
    }

    public function logging(): Logging
    {
        return $this->logging;
    }

    public function php(): Php
    {
        return $this->php;
    }

    public function phpunit(): PHPUnit
    {
        return $this->phpunit;
    }

    public function testSuite(): TestSuiteCollection
    {
        return $this->testSuite;
    }

    
    public function isDefault(): bool
    {
        return false;
    }

    
    public function wasLoadedFromFile(): bool
    {
        return false;
    }
}
