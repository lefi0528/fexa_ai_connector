<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use InvalidArgumentException;
use RuntimeException;


final class TcpServer extends EventEmitter implements ServerInterface
{
    private $master;
    private $loop;
    private $listening = false;

    
    public function __construct($uri, $loop = null, array $context = array())
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->loop = $loop ?: Loop::get();

        
        if ((string)(int)$uri === (string)$uri) {
            $uri = '127.0.0.1:' . $uri;
        }

        
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
        }

        
        if (\substr($uri, -2) === ':0') {
            $parts = \parse_url(\substr($uri, 0, -2));
            if ($parts) {
                $parts['port'] = 0;
            }
        } else {
            $parts = \parse_url($uri);
        }

        
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) || $parts['scheme'] !== 'tcp') {
            throw new \InvalidArgumentException(
                'Invalid URI "' . $uri . '" given (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            );
        }

        if (@\inet_pton(\trim($parts['host'], '[]')) === false) {
            throw new \InvalidArgumentException(
                'Given URI "' . $uri . '" does not contain a valid host IP (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            );
        }

        $this->master = @\stream_socket_server(
            $uri,
            $errno,
            $errstr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN,
            \stream_context_create(array('socket' => $context + array('backlog' => 511)))
        );
        if (false === $this->master) {
            if ($errno === 0) {
                
                
                $errno = SocketServer::errno($errstr);
            }

            throw new \RuntimeException(
                'Failed to listen on "' . $uri . '": ' . $errstr . SocketServer::errconst($errno),
                $errno
            );
        }
        \stream_set_blocking($this->master, false);

        $this->resume();
    }

    public function getAddress()
    {
        if (!\is_resource($this->master)) {
            return null;
        }

        $address = \stream_socket_get_name($this->master, false);

        
        $pos = \strrpos($address, ':');
        if ($pos !== false && \strpos($address, ':') < $pos && \substr($address, 0, 1) !== '[') {
            $address = '[' . \substr($address, 0, $pos) . ']:' . \substr($address, $pos + 1); 
        }

        return 'tcp://' . $address;
    }

    public function pause()
    {
        if (!$this->listening) {
            return;
        }

        $this->loop->removeReadStream($this->master);
        $this->listening = false;
    }

    public function resume()
    {
        if ($this->listening || !\is_resource($this->master)) {
            return;
        }

        $that = $this;
        $this->loop->addReadStream($this->master, function ($master) use ($that) {
            try {
                $newSocket = SocketServer::accept($master);
            } catch (\RuntimeException $e) {
                $that->emit('error', array($e));
                return;
            }
            $that->handleConnection($newSocket);
        });
        $this->listening = true;
    }

    public function close()
    {
        if (!\is_resource($this->master)) {
            return;
        }

        $this->pause();
        \fclose($this->master);
        $this->removeAllListeners();
    }

    
    public function handleConnection($socket)
    {
        $this->emit('connection', array(
            new Connection($socket, $this->loop)
        ));
    }
}
