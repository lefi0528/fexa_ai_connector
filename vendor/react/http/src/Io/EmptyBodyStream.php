<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\StreamInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;


class EmptyBodyStream extends EventEmitter implements StreamInterface, ReadableStreamInterface
{
    private $closed = false;

    public function isReadable()
    {
        return !$this->closed;
    }

    public function pause()
    {
        
    }

    public function resume()
    {
        
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

        $this->emit('close');
        $this->removeAllListeners();
    }

    public function getSize()
    {
        return 0;
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
        return ($key === null) ? array() : null;
    }
}
