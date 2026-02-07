<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\StreamInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;


class HttpBodyStream extends EventEmitter implements StreamInterface, ReadableStreamInterface
{
    public $input;
    private $closed = false;
    private $size;

    
    public function __construct(ReadableStreamInterface $input, $size)
    {
        $this->input = $input;
        $this->size = $size;

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('end', array($this, 'handleEnd'));
        $this->input->on('error', array($this, 'handleError'));
        $this->input->on('close', array($this, 'close'));
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    public function getSize()
    {
        return $this->size;
    }

    
    public function __toString()
    {
        return '';
    }

    
    public function detach()
    {
        return null;
    }

    
    public function tell()
    {
        throw new \BadMethodCallException();
    }

    
    public function eof()
    {
        throw new \BadMethodCallException();
    }

    
    public function isSeekable()
    {
        return false;
    }

    
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \BadMethodCallException();
    }

    
    public function rewind()
    {
        throw new \BadMethodCallException();
    }

    
    public function isWritable()
    {
        return false;
    }

    
    public function write($string)
    {
        throw new \BadMethodCallException();
    }

    
    public function read($length)
    {
        throw new \BadMethodCallException();
    }

    
    public function getContents()
    {
        return '';
    }

    
    public function getMetadata($key = null)
    {
        return null;
    }

    
    public function handleData($data)
    {
        $this->emit('data', array($data));
    }

    
    public function handleError(\Exception $e)
    {
        $this->emit('error', array($e));
        $this->close();
    }

    
    public function handleEnd()
    {
        if (!$this->closed) {
            $this->emit('end');
            $this->close();
        }
    }
}
