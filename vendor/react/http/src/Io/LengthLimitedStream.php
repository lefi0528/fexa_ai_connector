<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;


class LengthLimitedStream extends EventEmitter implements ReadableStreamInterface
{
    private $stream;
    private $closed = false;
    private $transferredLength = 0;
    private $maxLength;

    public function __construct(ReadableStreamInterface $stream, $maxLength)
    {
        $this->stream = $stream;
        $this->maxLength = $maxLength;

        $this->stream->on('data', array($this, 'handleData'));
        $this->stream->on('end', array($this, 'handleEnd'));
        $this->stream->on('error', array($this, 'handleError'));
        $this->stream->on('close', array($this, 'close'));
    }

    public function isReadable()
    {
        return !$this->closed && $this->stream->isReadable();
    }

    public function pause()
    {
        $this->stream->pause();
    }

    public function resume()
    {
        $this->stream->resume();
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

        $this->stream->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    
    public function handleData($data)
    {
        if (($this->transferredLength + \strlen($data)) > $this->maxLength) {
            
            $data = (string)\substr($data, 0, $this->maxLength - $this->transferredLength);
        }

        if ($data !== '') {
            $this->transferredLength += \strlen($data);
            $this->emit('data', array($data));
        }

        if ($this->transferredLength === $this->maxLength) {
            
            $this->emit('end');
            $this->close();
            $this->stream->removeListener('data', array($this, 'handleData'));
        }
    }

    
    public function handleError(\Exception $e)
    {
        $this->emit('error', array($e));
        $this->close();
    }

    
    public function handleEnd()
    {
        if (!$this->closed) {
            $this->handleError(new \Exception('Unexpected end event'));
        }
    }

}
