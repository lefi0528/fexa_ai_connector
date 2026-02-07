<?php

namespace React\Stream;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use InvalidArgumentException;

final class ReadableResourceStream extends EventEmitter implements ReadableStreamInterface
{
    
    private $stream;

    
    private $loop;

    
    private $bufferSize;

    private $closed = false;
    private $listening = false;

    
    public function __construct($stream, $loop = null, $readChunkSize = null)
    {
        if (!\is_resource($stream) || \get_resource_type($stream) !== "stream") {
             throw new InvalidArgumentException('First parameter must be a valid stream resource');
        }

        
        $meta = \stream_get_meta_data($stream);
        if (isset($meta['mode']) && $meta['mode'] !== '' && \strpos($meta['mode'], 'r') === \strpos($meta['mode'], '+')) {
            throw new InvalidArgumentException('Given stream resource is not opened in read mode');
        }

        
        
        if (\stream_set_blocking($stream, false) !== true) {
            throw new \RuntimeException('Unable to set stream resource to non-blocking mode');
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        
        
        
        
        
        
        
        
        if (\function_exists('stream_set_read_buffer') && !$this->isLegacyPipe($stream)) {
            \stream_set_read_buffer($stream, 0);
        }

        $this->stream = $stream;
        $this->loop = $loop ?: Loop::get();
        $this->bufferSize = ($readChunkSize === null) ? 65536 : (int)$readChunkSize;

        $this->resume();
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function pause()
    {
        if ($this->listening) {
            $this->loop->removeReadStream($this->stream);
            $this->listening = false;
        }
    }

    public function resume()
    {
        if (!$this->listening && !$this->closed) {
            $this->loop->addReadStream($this->stream, array($this, 'handleData'));
            $this->listening = true;
        }
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        return Util::pipe($this, $dest, $options);
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        $this->emit('close');
        $this->pause();
        $this->removeAllListeners();

        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }

    
    public function handleData()
    {
        $error = null;
        \set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$error) {
            $error = new \ErrorException(
                $errstr,
                0,
                $errno,
                $errfile,
                $errline
            );
        });

        $data = \stream_get_contents($this->stream, $this->bufferSize);

        \restore_error_handler();

        if ($error !== null) {
            $this->emit('error', array(new \RuntimeException('Unable to read from stream: ' . $error->getMessage(), 0, $error)));
            $this->close();
            return;
        }

        if ($data !== '') {
            $this->emit('data', array($data));
        } elseif (\feof($this->stream)) {
            
            $this->emit('end');
            $this->close();
        }
    }

    
    private function isLegacyPipe($resource)
    {
        if (\PHP_VERSION_ID < 50428 || (\PHP_VERSION_ID >= 50500 && \PHP_VERSION_ID < 50512)) {
            $meta = \stream_get_meta_data($resource);

            if (isset($meta['stream_type']) && $meta['stream_type'] === 'STDIO') {
                return true;
            }
        }
        return false;
    }
}
