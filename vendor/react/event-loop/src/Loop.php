<?php

namespace React\EventLoop;


final class Loop
{
    
    private static $instance;

    
    private static $stopped = false;

    
    public static function get()
    {
        if (self::$instance instanceof LoopInterface) {
            return self::$instance;
        }

        self::$instance = $loop = Factory::create();

        
        
        
        $hasRun = false;
        $loop->futureTick(function () use (&$hasRun) {
            $hasRun = true;
        });

        $stopped =& self::$stopped;
        register_shutdown_function(function () use ($loop, &$hasRun, &$stopped) {
            
            $error = error_get_last();
            if ((isset($error['type']) ? $error['type'] : 0) & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
                return;
            }

            if (!$hasRun && !$stopped) {
                $loop->run();
            }
        });
        

        return self::$instance;
    }

    
    public static function set(LoopInterface $loop)
    {
        self::$instance = $loop;
    }

    
    public static function addReadStream($stream, $listener)
    {
        
        if (self::$instance === null) {
            self::get();
        }
        self::$instance->addReadStream($stream, $listener);
    }

    
    public static function addWriteStream($stream, $listener)
    {
        
        if (self::$instance === null) {
            self::get();
        }
        self::$instance->addWriteStream($stream, $listener);
    }

    
    public static function removeReadStream($stream)
    {
        if (self::$instance !== null) {
            self::$instance->removeReadStream($stream);
        }
    }

    
    public static function removeWriteStream($stream)
    {
        if (self::$instance !== null) {
            self::$instance->removeWriteStream($stream);
        }
    }

    
    public static function addTimer($interval, $callback)
    {
        
        if (self::$instance === null) {
            self::get();
        }
        return self::$instance->addTimer($interval, $callback);
    }

    
    public static function addPeriodicTimer($interval, $callback)
    {
        
        if (self::$instance === null) {
            self::get();
        }
        return self::$instance->addPeriodicTimer($interval, $callback);
    }

    
    public static function cancelTimer(TimerInterface $timer)
    {
        if (self::$instance !== null) {
            self::$instance->cancelTimer($timer);
        }
    }

    
    public static function futureTick($listener)
    {
        
        if (self::$instance === null) {
            self::get();
        }

        self::$instance->futureTick($listener);
    }

    
    public static function addSignal($signal, $listener)
    {
        
        if (self::$instance === null) {
            self::get();
        }

        self::$instance->addSignal($signal, $listener);
    }

    
    public static function removeSignal($signal, $listener)
    {
        if (self::$instance !== null) {
            self::$instance->removeSignal($signal, $listener);
        }
    }

    
    public static function run()
    {
        
        if (self::$instance === null) {
            self::get();
        }

        self::$instance->run();
    }

    
    public static function stop()
    {
        self::$stopped = true;
        if (self::$instance !== null) {
            self::$instance->stop();
        }
    }
}
