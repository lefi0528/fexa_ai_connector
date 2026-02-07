<?php

namespace React\EventLoop;

use React\EventLoop\Tick\FutureTickQueue;
use React\EventLoop\Timer\Timer;
use SplObjectStorage;


final class ExtUvLoop implements LoopInterface
{
    private $uv;
    private $futureTickQueue;
    private $timers;
    private $streamEvents = array();
    private $readStreams = array();
    private $writeStreams = array();
    private $running;
    private $signals;
    private $signalEvents = array();
    private $streamListener;

    public function __construct()
    {
        if (!\function_exists('uv_loop_new')) {
            throw new \BadMethodCallException('Cannot create LibUvLoop, ext-uv extension missing');
        }

        $this->uv = \uv_loop_new();
        $this->futureTickQueue = new FutureTickQueue();
        $this->timers = new SplObjectStorage();
        $this->streamListener = $this->createStreamListener();
        $this->signals = new SignalsHandler();
    }

    
    public function getUvLoop()
    {
        return $this->uv;
    }

    
    public function addReadStream($stream, $listener)
    {
        if (isset($this->readStreams[(int) $stream])) {
            return;
        }

        $this->readStreams[(int) $stream] = $listener;
        $this->addStream($stream);
    }

    
    public function addWriteStream($stream, $listener)
    {
        if (isset($this->writeStreams[(int) $stream])) {
            return;
        }

        $this->writeStreams[(int) $stream] = $listener;
        $this->addStream($stream);
    }

    
    public function removeReadStream($stream)
    {
        if (!isset($this->streamEvents[(int) $stream])) {
            return;
        }

        unset($this->readStreams[(int) $stream]);
        $this->removeStream($stream);
    }

    
    public function removeWriteStream($stream)
    {
        if (!isset($this->streamEvents[(int) $stream])) {
            return;
        }

        unset($this->writeStreams[(int) $stream]);
        $this->removeStream($stream);
    }

    
    public function addTimer($interval, $callback)
    {
        $timer = new Timer($interval, $callback, false);

        $that = $this;
        $timers = $this->timers;
        $callback = function () use ($timer, $timers, $that) {
            \call_user_func($timer->getCallback(), $timer);

            if ($timers->offsetExists($timer)) {
                $that->cancelTimer($timer);
            }
        };

        $event = \uv_timer_init($this->uv);
        $this->timers->offsetSet($timer, $event);
        \uv_timer_start(
            $event,
            $this->convertFloatSecondsToMilliseconds($interval),
            0,
            $callback
        );

        return $timer;
    }

    
    public function addPeriodicTimer($interval, $callback)
    {
        $timer = new Timer($interval, $callback, true);

        $callback = function () use ($timer) {
            \call_user_func($timer->getCallback(), $timer);
        };

        $interval = $this->convertFloatSecondsToMilliseconds($interval);
        $event = \uv_timer_init($this->uv);
        $this->timers->offsetSet($timer, $event);
        \uv_timer_start(
            $event,
            $interval,
            (int) $interval === 0 ? 1 : $interval,
            $callback
        );

        return $timer;
    }

    
    public function cancelTimer(TimerInterface $timer)
    {
        if (isset($this->timers[$timer])) {
            @\uv_timer_stop($this->timers[$timer]);
            $this->timers->offsetUnset($timer);
        }
    }

    
    public function futureTick($listener)
    {
        $this->futureTickQueue->add($listener);
    }

    public function addSignal($signal, $listener)
    {
        $this->signals->add($signal, $listener);

        if (!isset($this->signalEvents[$signal])) {
            $signals = $this->signals;
            $this->signalEvents[$signal] = \uv_signal_init($this->uv);
            \uv_signal_start($this->signalEvents[$signal], function () use ($signals, $signal) {
                $signals->call($signal);
            }, $signal);
        }
    }

    public function removeSignal($signal, $listener)
    {
        $this->signals->remove($signal, $listener);

        if (isset($this->signalEvents[$signal]) && $this->signals->count($signal) === 0) {
            \uv_signal_stop($this->signalEvents[$signal]);
            unset($this->signalEvents[$signal]);
        }
    }

    
    public function run()
    {
        $this->running = true;

        while ($this->running) {
            $this->futureTickQueue->tick();

            $hasPendingCallbacks = !$this->futureTickQueue->isEmpty();
            $wasJustStopped = !$this->running;
            $nothingLeftToDo = !$this->readStreams
                && !$this->writeStreams
                && !$this->timers->count()
                && $this->signals->isEmpty();

            
            
            
            $flags = \UV::RUN_ONCE;
            if ($wasJustStopped || $hasPendingCallbacks) {
                $flags = \UV::RUN_NOWAIT;
            } elseif ($nothingLeftToDo) {
                break;
            }

            \uv_run($this->uv, $flags);
        }
    }

    
    public function stop()
    {
        $this->running = false;
    }

    private function addStream($stream)
    {
        if (!isset($this->streamEvents[(int) $stream])) {
            $this->streamEvents[(int)$stream] = \uv_poll_init_socket($this->uv, $stream);
        }

        if ($this->streamEvents[(int) $stream] !== false) {
            $this->pollStream($stream);
        }
    }

    private function removeStream($stream)
    {
        if (!isset($this->streamEvents[(int) $stream])) {
            return;
        }

        if (!isset($this->readStreams[(int) $stream])
            && !isset($this->writeStreams[(int) $stream])) {
            \uv_poll_stop($this->streamEvents[(int) $stream]);
            \uv_close($this->streamEvents[(int) $stream]);
            unset($this->streamEvents[(int) $stream]);
            return;
        }

        $this->pollStream($stream);
    }

    private function pollStream($stream)
    {
        if (!isset($this->streamEvents[(int) $stream])) {
            return;
        }

        $flags = 0;
        if (isset($this->readStreams[(int) $stream])) {
            $flags |= \UV::READABLE;
        }

        if (isset($this->writeStreams[(int) $stream])) {
            $flags |= \UV::WRITABLE;
        }

        \uv_poll_start($this->streamEvents[(int) $stream], $flags, $this->streamListener);
    }

    
    private function createStreamListener()
    {
        $callback = function ($event, $status, $events, $stream) {
            
            if ($status !== 0) {
                $this->pollStream($stream);

                
                
                if ($events === 0) {
                    $events = \UV::READABLE | \UV::WRITABLE;
                }
            }

            if (isset($this->readStreams[(int) $stream]) && ($events & \UV::READABLE)) {
                \call_user_func($this->readStreams[(int) $stream], $stream);
            }

            if (isset($this->writeStreams[(int) $stream]) && ($events & \UV::WRITABLE)) {
                \call_user_func($this->writeStreams[(int) $stream], $stream);
            }
        };

        return $callback;
    }

    
    private function convertFloatSecondsToMilliseconds($interval)
    {
        if ($interval < 0) {
            return 0;
        }

        $maxValue = (int) (\PHP_INT_MAX / 1000);
        $intervalOverflow = false;
        if (PHP_VERSION_ID > 80499 && $interval >= \PHP_INT_MAX + 1) {
            $intervalOverflow = true;
        } else {
            $intInterval = (int) $interval;
            if (($intInterval <= 0 && $interval > 1) || $intInterval >= $maxValue) {
                $intervalOverflow = true;
            }
        }

        if ($intervalOverflow) {
            throw new \InvalidArgumentException(
                "Interval overflow, value must be lower than '{$maxValue}', but '{$interval}' passed."
            );
        }

        return (int) \floor($interval * 1000);
    }
}
