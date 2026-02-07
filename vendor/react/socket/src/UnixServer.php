<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use InvalidArgumentException;
use RuntimeException;


final class UnixServer extends EventEmitter implements ServerInterface
{
    private $master;
    private $loop;
    private $listening = false;

    
    public function __construct($path, $loop = null, array $context = array())
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->loop = $loop ?: Loop::get();

        if (\strpos($path, '://') === false) {
            $path = 'unix://' . $path;
        } elseif (\substr($path, 0, 7) !== 'unix://') {
            throw new \InvalidArgumentException(
                'Given URI "' . $path . '" is invalid (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            );
        }

        $errno = 0;
        $errstr = '';
        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
            
            
            
            if (\preg_match('/\(([^\)]+)\)|\[(\d+)\]: (.*)/', $error, $match)) {
                $errstr = isset($match[3]) ? $match['3'] : $match[1];
                $errno = isset($match[2]) ? (int)$match[2] : 0;
            }
        });

        $this->master = \stream_socket_server(
            $path,
            $errno,
            $errstr,
            \STREAM_SERVER_BIND | \STREAM_SERVER_LISTEN,
            \stream_context_create(array('socket' => $context))
        );

        \restore_error_handler();

        if (false === $this->master) {
            throw new \RuntimeException(
                'Failed to listen on Unix domain socket "' . $path . '": ' . $errstr . SocketServer::errconst($errno),
                $errno
            );
        }
        \stream_set_blocking($this->master, 0);

        $this->resume();
    }

    public function getAddress()
    {
        if (!\is_resource($this->master)) {
            return null;
        }

        return 'unix://' . \stream_socket_get_name($this->master, false);
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
        if ($this->listening || !is_resource($this->master)) {
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
        $connection = new Connection($socket, $this->loop);
        $connection->unix = true;

        $this->emit('connection', array(
            $connection
        ));
    }
}
