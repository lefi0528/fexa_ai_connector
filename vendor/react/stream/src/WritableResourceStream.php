<?php

namespace React\Stream;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

final class WritableResourceStream extends EventEmitter implements WritableStreamInterface
{
    private $stream;

    
    private $loop;

    
    private $softLimit;

    
    private $writeChunkSize;

    private $listening = false;
    private $writable = true;
    private $closed = false;
    private $data = '';

    
    public function __construct($stream, $loop = null, $writeBufferSoftLimit = null, $writeChunkSize = null)
    {
        if (!\is_resource($stream) || \get_resource_type($stream) !== "stream") {
            throw new \InvalidArgumentException('First parameter must be a valid stream resource');
        }

        
        $meta = \stream_get_meta_data($stream);
        if (isset($meta['mode']) && $meta['mode'] !== '' && \strtr($meta['mode'], 'waxc+', '.....') === $meta['mode']) {
            throw new \InvalidArgumentException('Given stream resource is not opened in write mode');
        }

        
        
        if (\stream_set_blocking($stream, false) !== true) {
            throw new \RuntimeException('Unable to set stream resource to non-blocking mode');
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->stream = $stream;
        $this->loop = $loop ?: Loop::get();
        $this->softLimit = ($writeBufferSoftLimit === null) ? 65536 : (int)$writeBufferSoftLimit;
        $this->writeChunkSize = ($writeChunkSize === null) ? -1 : (int)$writeChunkSize;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($data)
    {
        if (!$this->writable) {
            return false;
        }

        $this->data .= $data;

        if (!$this->listening && $this->data !== '') {
            $this->listening = true;

            $this->loop->addWriteStream($this->stream, array($this, 'handleWrite'));
        }

        return !isset($this->data[$this->softLimit - 1]);
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        $this->writable = false;

        
        
        if ($this->data === '') {
            $this->close();
        }
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        if ($this->listening) {
            $this->listening = false;
            $this->loop->removeWriteStream($this->stream);
        }

        $this->closed = true;
        $this->writable = false;
        $this->data = '';

        $this->emit('close');
        $this->removeAllListeners();

        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }

    
    public function handleWrite()
    {
        $error = null;
        \set_error_handler(function ($_, $errstr) use (&$error) {
            $error = $errstr;
        });

        if ($this->writeChunkSize === -1) {
            $sent = \fwrite($this->stream, $this->data);
        } else {
            $sent = \fwrite($this->stream, $this->data, $this->writeChunkSize);
        }

        \restore_error_handler();

        
        
        
        
        
        
        
        
        if (($sent === 0 || $sent === false) && $error !== null) {
            $this->emit('error', array(new \RuntimeException('Unable to write to stream: ' . $error)));
            $this->close();

            return;
        }

        $exceeded = isset($this->data[$this->softLimit - 1]);
        $this->data = (string) \substr($this->data, $sent);

        
        if ($exceeded && !isset($this->data[$this->softLimit - 1])) {
            $this->emit('drain');
        }

        
        if ($this->data === '') {
            
            if ($this->listening) {
                $this->loop->removeWriteStream($this->stream);
                $this->listening = false;
            }

            
            if (!$this->writable) {
                $this->close();
            }
        }
    }
}
