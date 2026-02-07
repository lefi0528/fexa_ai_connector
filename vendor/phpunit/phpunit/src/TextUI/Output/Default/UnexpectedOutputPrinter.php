<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Output\Default;

use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Event\Facade;
use PHPUnit\Event\Test\PrintedUnexpectedOutput;
use PHPUnit\Event\Test\PrintedUnexpectedOutputSubscriber;
use PHPUnit\Event\UnknownSubscriberTypeException;
use PHPUnit\TextUI\Output\Printer;

final class UnexpectedOutputPrinter implements PrintedUnexpectedOutputSubscriber
{
    private readonly Printer $printer;

    
    public function __construct(Printer $printer, Facade $facade)
    {
        $this->printer = $printer;

        $facade->registerSubscriber($this);
    }

    public function notify(PrintedUnexpectedOutput $event): void
    {
        $this->printer->print($event->output());
    }
}
