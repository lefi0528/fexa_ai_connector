<?php

namespace React\Stream;

use Evenement\EventEmitter;
use InvalidArgumentException;


final class ThroughStream extends EventEmitter implements DuplexStreamInterface
{
    private $readable = true;
    private $writable = true;
    private $closed = false;
    private $paused = false;
    private $drain = false;
    private $callback;

    public function __construct($callback = null)
    {
        if ($callback !== null && !\is_callable($callback)) {
            throw new InvalidArgumentException('Invalid transformation callback given');
        }

        $this->callback = $callback;
    }

    public function pause()
    {
        
        $this->paused = $this->readable;
    }

    public function resume()
    {
        $this->paused = false;

        
        if ($this->drain) {
            $this->drain = false;
            $this->emit('drain');
        }
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        return Util::pipe($this, $dest, $options);
    }

    public function isReadable()
    {
        return $this->readable;
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

        if ($this->callback !== null) {
            try {
                $data = \call_user_func($this->callback, $data);
            } catch (\Exception $e) {
                $this->emit('error', array($e));
                $this->close();

                return false;
            }
        }

        $this->emit('data', array($data));

        
        if ($this->paused) {
            $this->drain = true;
        }

        
        return $this->writable && !$this->paused;
    }

    public function end($data = null)
    {
        if (!$this->writable) {
            return;
        }

        if (null !== $data) {
            $this->write($data);

            
            if (!$this->writable) {
                return;
            }
        }

        $this->readable = false;
        $this->writable = false;
        $this->paused = false;
        $this->drain = false;

        $this->emit('end');
        $this->close();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->readable = false;
        $this->writable = false;
        $this->paused = false;
        $this->drain = false;

        $this->closed = true;
        $this->callback = null;

        $this->emit('close');
        $this->removeAllListeners();
    }
}
