<?php

declare(strict_types=1);

namespace PhpMcp\Client\Transport\Http;

use Evenement\EventEmitter;
use Psr\Http\Message\StreamInterface as Psr7StreamInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;


class Psr7StreamAdapter extends EventEmitter implements ReadableStreamInterface
{
    private bool $closed = false;

    private ?TimerInterface $timer = null;

    private float $readInterval;

    private int $chunkSize;

    public function __construct(
        private Psr7StreamInterface $psrStream,
        private LoopInterface $loop,
        float $readInterval = 0.01, 
        int $chunkSize = 8192 
    ) {
        if (! $psrStream->isReadable()) {
            
            $this->close(); 
            throw new \InvalidArgumentException('PSR-7 stream provided is not readable.');
        }
        $this->readInterval = $readInterval;
        $this->chunkSize = $chunkSize;
        $this->resume(); 
    }

    public function isReadable(): bool
    {
        return ! $this->closed && $this->psrStream->isReadable();
    }

    public function pause(): void
    {
        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }
    }

    public function resume(): void
    {
        if ($this->closed || $this->timer) {
            return; 
        }

        
        $this->timer = $this->loop->addPeriodicTimer($this->readInterval, function () {
            if ($this->closed || ! $this->psrStream->isReadable()) {
                $this->close(); 

                return;
            }

            try {
                
                if ($this->psrStream->eof()) {
                    $this->emit('end');
                    $this->close();

                    return;
                }

                
                
                $data = $this->psrStream->read($this->chunkSize);

                if ($data === '') {
                    
                    
                    if ($this->psrStream->eof()) {
                        $this->emit('end');
                        $this->close();
                    }
                    
                } else {
                    
                    $this->emit('data', [$data]);
                }
            } catch (\Throwable $e) {
                $this->emit('error', [$e]);
                $this->close();
            }
        });
    }

    public function pipe(\React\Stream\WritableStreamInterface $dest, array $options = []): \React\Stream\WritableStreamInterface
    {
        
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        if ($this->timer) {
            $this->loop->cancelTimer($this->timer);
            $this->timer = null;
        }

        
        if (method_exists($this->psrStream, 'close')) {
            $this->psrStream->close();
        }

        $this->emit('close');
        $this->removeAllListeners();
    }
}
