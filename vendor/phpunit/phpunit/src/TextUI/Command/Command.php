<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Command;


interface Command
{
    public function execute(): Result;
}
