<?php

namespace React\EventLoop;


final class Factory
{
    
    public static function create()
    {
        $loop = self::construct();

        Loop::set($loop);

        return $loop;
    }

    
    private static function construct()
    {
        
        if (\function_exists('uv_loop_new')) {
            
            return new ExtUvLoop();
        }

        if (\class_exists('libev\EventLoop', false)) {
            return new ExtLibevLoop();
        }

        if (\class_exists('EvLoop', false)) {
            return new ExtEvLoop();
        }

        if (\class_exists('EventBase', false)) {
            return new ExtEventLoop();
        }

        if (\function_exists('event_base_new') && \PHP_MAJOR_VERSION === 5) {
            
            return new ExtLibeventLoop();
        }

        return new StreamSelectLoop();
        
    }
}
