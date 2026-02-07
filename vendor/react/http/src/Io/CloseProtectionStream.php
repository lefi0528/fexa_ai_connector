<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;


class CloseProtectionStream extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    private $closed = false;
    private $paused = false;

    
    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

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
        if ($this->closed) {
            return;
        }

        $this->paused = true;
        $this->input->pause();
    }

    public function resume()
    {
        if ($this->closed) {
            return;
        }

        $this->paused = false;
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

         
         $this->input->removeListener('data', array($this, 'handleData'));
         $this->input->removeListener('error', array($this, 'handleError'));
         $this->input->removeListener('end', array($this, 'handleEnd'));
         $this->input->removeListener('close', array($this, 'close'));

         
         if ($this->paused) {
             $this->paused = false;
             $this->input->resume();
         }

         $this->emit('close');
         $this->removeAllListeners();
     }

     
     public function handleData($data)
     {
        $this->emit('data', array($data));
     }

     
     public function handleEnd()
     {
         $this->emit('end');
         $this->close();
     }

     
     public function handleError(\Exception $e)
     {
         $this->emit('error', array($e));
     }
}
