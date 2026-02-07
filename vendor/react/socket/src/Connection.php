<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Stream\DuplexResourceStream;
use React\Stream\Util;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;


class Connection extends EventEmitter implements ConnectionInterface
{
    
    public $unix = false;

    
    public $encryptionEnabled = false;

    
    public $stream;

    private $input;

    public function __construct($resource, LoopInterface $loop)
    {
        
        
        
        
        
        
        
        
        
        $clearCompleteBuffer = \PHP_VERSION_ID < 70215 || (\PHP_VERSION_ID >= 70300 && \PHP_VERSION_ID < 70303);

        
        
        
        
        
        
        
        
        $limitWriteChunks = (\PHP_VERSION_ID < 70018 || (\PHP_VERSION_ID >= 70100 && \PHP_VERSION_ID < 70104));

        $this->input = new DuplexResourceStream(
            $resource,
            $loop,
            $clearCompleteBuffer ? -1 : null,
            new WritableResourceStream($resource, $loop, null, $limitWriteChunks ? 8192 : null)
        );

        $this->stream = $resource;

        Util::forwardEvents($this->input, $this, array('data', 'end', 'error', 'close', 'pipe', 'drain'));

        $this->input->on('close', array($this, 'close'));
    }

    public function isReadable()
    {
        return $this->input->isReadable();
    }

    public function isWritable()
    {
        return $this->input->isWritable();
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
        return $this->input->pipe($dest, $options);
    }

    public function write($data)
    {
        return $this->input->write($data);
    }

    public function end($data = null)
    {
        $this->input->end($data);
    }

    public function close()
    {
        $this->input->close();
        $this->handleClose();
        $this->removeAllListeners();
    }

    public function handleClose()
    {
        if (!\is_resource($this->stream)) {
            return;
        }

        
        
        
        @\stream_socket_shutdown($this->stream, \STREAM_SHUT_RDWR);
    }

    public function getRemoteAddress()
    {
        if (!\is_resource($this->stream)) {
            return null;
        }

        return $this->parseAddress(\stream_socket_get_name($this->stream, true));
    }

    public function getLocalAddress()
    {
        if (!\is_resource($this->stream)) {
            return null;
        }

        return $this->parseAddress(\stream_socket_get_name($this->stream, false));
    }

    private function parseAddress($address)
    {
        if ($address === false) {
            return null;
        }

        if ($this->unix) {
            
            
            if (\substr($address, -1) === ':' && \defined('HHVM_VERSION_ID') && \HHVM_VERSION_ID < 31900) {
                $address = (string)\substr($address, 0, -1); 
            }

            
            
            if ($address === '' || $address[0] === "\x00" ) {
                return null; 
            }

            return 'unix://' . $address;
        }

        
        $pos = \strrpos($address, ':');
        if ($pos !== false && \strpos($address, ':') < $pos && \substr($address, 0, 1) !== '[') {
            $address = '[' . \substr($address, 0, $pos) . ']:' . \substr($address, $pos + 1); 
        }

        return ($this->encryptionEnabled ? 'tls' : 'tcp') . '://' . $address;
    }
}
