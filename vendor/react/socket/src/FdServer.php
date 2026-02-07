<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;


final class FdServer extends EventEmitter implements ServerInterface
{
    private $master;
    private $loop;
    private $unix = false;
    private $listening = false;

    
    public function __construct($fd, $loop = null)
    {
        if (\preg_match('#^php://fd/(\d+)$#', $fd, $m)) {
            $fd = (int) $m[1];
        }
        if (!\is_int($fd) || $fd < 0 || $fd >= \PHP_INT_MAX) {
            throw new \InvalidArgumentException(
                'Invalid FD number given (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            );
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->loop = $loop ?: Loop::get();

        $errno = 0;
        $errstr = '';
        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
            
            
            \preg_match('/\[(\d+)\]: (.*)/', $error, $m);
            $errno = isset($m[1]) ? (int) $m[1] : 0;
            $errstr = isset($m[2]) ? $m[2] : $error;
        });

        $this->master = \fopen('php://fd/' . $fd, 'r+');

        \restore_error_handler();

        if (false === $this->master) {
            throw new \RuntimeException(
                'Failed to listen on FD ' . $fd . ': ' . $errstr . SocketServer::errconst($errno),
                $errno
            );
        }

        $meta = \stream_get_meta_data($this->master);
        if (!isset($meta['stream_type']) || $meta['stream_type'] !== 'tcp_socket') {
            \fclose($this->master);

            $errno = \defined('SOCKET_ENOTSOCK') ? \SOCKET_ENOTSOCK : 88;
            $errstr = \function_exists('socket_strerror') ? \socket_strerror($errno) : 'Not a socket';

            throw new \RuntimeException(
                'Failed to listen on FD ' . $fd . ': ' . $errstr . ' (ENOTSOCK)',
                $errno
            );
        }

        
        
        if (\stream_socket_get_name($this->master, true) !== false) {
            \fclose($this->master);

            $errno = \defined('SOCKET_EISCONN') ? \SOCKET_EISCONN : 106;
            $errstr = \function_exists('socket_strerror') ? \socket_strerror($errno) : 'Socket is connected';

            throw new \RuntimeException(
                'Failed to listen on FD ' . $fd . ': ' . $errstr . ' (EISCONN)',
                $errno
            );
        }

        
        
        $this->unix = \parse_url($this->getAddress(), \PHP_URL_PORT) === false;

        \stream_set_blocking($this->master, false);

        $this->resume();
    }

    public function getAddress()
    {
        if (!\is_resource($this->master)) {
            return null;
        }

        $address = \stream_socket_get_name($this->master, false);

        if ($this->unix === true) {
            return 'unix://' . $address;
        }

        
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
        $connection = new Connection($socket, $this->loop);
        $connection->unix = $this->unix;

        $this->emit('connection', array($connection));
    }
}
