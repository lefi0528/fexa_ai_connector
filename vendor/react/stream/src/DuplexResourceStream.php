<?php

namespace React\Stream;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use InvalidArgumentException;

final class DuplexResourceStream extends EventEmitter implements DuplexStreamInterface
{
    private $stream;

    
    private $loop;

    
    private $bufferSize;
    private $buffer;

    private $readable = true;
    private $writable = true;
    private $closing = false;
    private $listening = false;

    
    public function __construct($stream, $loop = null, $readChunkSize = null, $buffer = null)
    {
        if (!\is_resource($stream) || \get_resource_type($stream) !== "stream") {
             throw new InvalidArgumentException('First parameter must be a valid stream resource');
        }

        
        $meta = \stream_get_meta_data($stream);
        if (isset($meta['mode']) && $meta['mode'] !== '' && \strpos($meta['mode'], '+') === false) {
            throw new InvalidArgumentException('Given stream resource is not opened in read and write mode');
        }

        
        
        if ($buffer !== null && !$buffer instanceof WritableResourceStream && \stream_set_blocking($stream, false) !== true) {
            throw new \RuntimeException('Unable to set stream resource to non-blocking mode');
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }
        if ($buffer !== null && !$buffer instanceof WritableStreamInterface) { 
            throw new \InvalidArgumentException('Argument #4 ($buffer) expected null|React\Stream\WritableStreamInterface');
        }

        
        
        
        
        
        
        
        
        if (\function_exists('stream_set_read_buffer') && !$this->isLegacyPipe($stream)) {
            \stream_set_read_buffer($stream, 0);
        }

        if ($buffer === null) {
            $buffer = new WritableResourceStream($stream, $loop);
        }

        $this->stream = $stream;
        $this->loop = $loop ?: Loop::get();
        $this->bufferSize = ($readChunkSize === null) ? 65536 : (int)$readChunkSize;
        $this->buffer = $buffer;

        $that = $this;

        $this->buffer->on('error', function ($error) use ($that) {
            $that->emit('error', array($error));
        });

        $this->buffer->on('close', array($this, 'close'));

        $this->buffer->on('drain', function () use ($that) {
            $that->emit('drain');
        });

        $this->resume();
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
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
        if (!$this->listening && $this->readable) {
            $this->loop->addReadStream($this->stream, array($this, 'handleData'));
            $this->listening = true;
        }
    }

    public function write($data)
    {
        if (!$this->writable) {
            return false;
        }

        return $this->buffer->write($data);
    }

    public function close()
    {
        if (!$this->writable && !$this->closing) {
            return;
        }

        $this->closing = false;

        $this->readable = false;
        $this->writable = false;

        $this->emit('close');
        $this->pause();
        $this->buffer->close();
        $this->removeAllListeners();

        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }

    public function end($data = null)
    {
        if (!$this->writable) {
            return;
        }

        $this->closing = true;

        $this->readable = false;
        $this->writable = false;
        $this->pause();

        $this->buffer->end($data);
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        return Util::pipe($this, $dest, $options);
    }

    
    public function handleData($stream)
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

        $data = \stream_get_contents($stream, $this->bufferSize);

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
