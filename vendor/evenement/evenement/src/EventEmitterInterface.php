<?php declare(strict_types=1);



namespace Evenement;

interface EventEmitterInterface
{
    public function on($event, callable $listener);
    public function once($event, callable $listener);
    public function removeListener($event, callable $listener);
    public function removeAllListeners($event = null);
    public function listeners($event = null);
    public function emit($event, array $arguments = []);
}
