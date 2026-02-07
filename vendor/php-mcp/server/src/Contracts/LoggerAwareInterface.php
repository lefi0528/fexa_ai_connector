<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

use Psr\Log\LoggerInterface;


interface LoggerAwareInterface
{
    public function setLogger(LoggerInterface $logger): void;
}
