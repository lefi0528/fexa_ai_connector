<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Command;


final class ShowVersionCommand implements Command
{
    public function execute(): Result
    {
        return Result::from();
    }
}
