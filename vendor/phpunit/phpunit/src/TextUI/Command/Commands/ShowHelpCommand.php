<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Command;

use PHPUnit\TextUI\Help;


final class ShowHelpCommand implements Command
{
    private readonly int $shellExitCode;

    public function __construct(int $shellExitCode)
    {
        $this->shellExitCode = $shellExitCode;
    }

    public function execute(): Result
    {
        return Result::from(
            (new Help)->generate(),
            $this->shellExitCode,
        );
    }
}
