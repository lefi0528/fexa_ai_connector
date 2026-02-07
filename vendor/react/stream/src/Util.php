<?php

namespace React\Stream;

final class Util
{
    
    public static function pipe(ReadableStreamInterface $source, WritableStreamInterface $dest, array $options = array())
    {
        
        if (!$source->isReadable()) {
            return $dest;
        }

        
        if (!$dest->isWritable()) {
            $source->pause();

            return $dest;
        }

        $dest->emit('pipe', array($source));

        
        $source->on('data', $dataer = function ($data) use ($source, $dest) {
            $feedMore = $dest->write($data);

            if (false === $feedMore) {
                $source->pause();
            }
        });
        $dest->on('close', function () use ($source, $dataer) {
            $source->removeListener('data', $dataer);
            $source->pause();
        });

        
        $dest->on('drain', $drainer = function () use ($source) {
            $source->resume();
        });
        $source->on('close', function () use ($dest, $drainer) {
            $dest->removeListener('drain', $drainer);
        });

        
        $end = isset($options['end']) ? $options['end'] : true;
        if ($end) {
            $source->on('end', $ender = function () use ($dest) {
                $dest->end();
            });
            $dest->on('close', function () use ($source, $ender) {
                $source->removeListener('end', $ender);
            });
        }

        return $dest;
    }

    public static function forwardEvents($source, $target, array $events)
    {
        foreach ($events as $event) {
            $source->on($event, function () use ($event, $target) {
                $target->emit($event, \func_get_args());
            });
        }
    }
}
