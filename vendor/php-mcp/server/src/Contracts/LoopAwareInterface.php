<?php

declare(strict_types=1);

namespace PhpMcp\Server\Contracts;

use React\EventLoop\LoopInterface;


interface LoopAwareInterface
{
    public function setLoop(LoopInterface $loop): void;
}
