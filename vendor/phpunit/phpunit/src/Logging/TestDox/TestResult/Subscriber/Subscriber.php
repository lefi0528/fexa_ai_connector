<?php declare(strict_types=1);

namespace PHPUnit\Logging\TestDox;


abstract class Subscriber
{
    private readonly TestResultCollector $collector;

    public function __construct(TestResultCollector $collector)
    {
        $this->collector = $collector;
    }

    protected function collector(): TestResultCollector
    {
        return $this->collector;
    }
}
