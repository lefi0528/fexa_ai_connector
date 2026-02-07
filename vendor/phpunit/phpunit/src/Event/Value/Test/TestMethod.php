<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;

use function assert;
use function is_int;
use function sprintf;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Metadata\MetadataCollection;


final class TestMethod extends Test
{
    
    private readonly string $className;

    
    private readonly string $methodName;

    
    private readonly int $line;
    private readonly TestDox $testDox;
    private readonly MetadataCollection $metadata;
    private readonly TestDataCollection $testData;

    
    public function __construct(string $className, string $methodName, string $file, int $line, TestDox $testDox, MetadataCollection $metadata, TestDataCollection $testData)
    {
        parent::__construct($file);

        $this->className  = $className;
        $this->methodName = $methodName;
        $this->line       = $line;
        $this->testDox    = $testDox;
        $this->metadata   = $metadata;
        $this->testData   = $testData;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    
    public function methodName(): string
    {
        return $this->methodName;
    }

    
    public function line(): int
    {
        return $this->line;
    }

    public function testDox(): TestDox
    {
        return $this->testDox;
    }

    public function metadata(): MetadataCollection
    {
        return $this->metadata;
    }

    public function testData(): TestDataCollection
    {
        return $this->testData;
    }

    
    public function isTestMethod(): bool
    {
        return true;
    }

    
    public function id(): string
    {
        $buffer = $this->className . '::' . $this->methodName;

        if ($this->testData()->hasDataFromDataProvider()) {
            $buffer .= '#' . $this->testData->dataFromDataProvider()->dataSetName();
        }

        return $buffer;
    }

    
    public function nameWithClass(): string
    {
        return $this->className . '::' . $this->name();
    }

    
    public function name(): string
    {
        if (!$this->testData->hasDataFromDataProvider()) {
            return $this->methodName;
        }

        $dataSetName = $this->testData->dataFromDataProvider()->dataSetName();

        if (is_int($dataSetName)) {
            $dataSetName = sprintf(
                ' with data set #%d',
                $dataSetName,
            );
        } else {
            $dataSetName = sprintf(
                ' with data set "%s"',
                $dataSetName,
            );
        }

        return $this->methodName . $dataSetName;
    }
}
