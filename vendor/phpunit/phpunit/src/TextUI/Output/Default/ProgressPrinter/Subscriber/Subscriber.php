<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Output\Default\ProgressPrinter;


abstract class Subscriber
{
    private readonly ProgressPrinter $printer;

    public function __construct(ProgressPrinter $printer)
    {
        $this->printer = $printer;
    }

    protected function printer(): ProgressPrinter
    {
        return $this->printer;
    }
}
