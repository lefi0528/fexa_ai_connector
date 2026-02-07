<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;

final class SocketServer extends EventEmitter implements ServerInterface
{
    private $server;

    
    public function __construct($uri, array $context = array(), $loop = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #3 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        
        $context += array(
            'tcp' => array(),
            'tls' => array(),
            'unix' => array()
        );

        $scheme = 'tcp';
        $pos = \strpos($uri, '://');
        if ($pos !== false) {
            $scheme = \substr($uri, 0, $pos);
        }

        if ($scheme === 'unix') {
            $server = new UnixServer($uri, $loop, $context['unix']);
        } elseif ($scheme === 'php') {
            $server = new FdServer($uri, $loop);
        } else {
            if (preg_match('#^(?:\w+://)?\d+$#', $uri)) {
                throw new \InvalidArgumentException(
                    'Invalid URI given (EINVAL)',
                    \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
                );
            }

            $server = new TcpServer(str_replace('tls://', '', $uri), $loop, $context['tcp']);

            if ($scheme === 'tls') {
                $server = new SecureServer($server, $loop, $context['tls']);
            }
        }

        $this->server = $server;

        $that = $this;
        $server->on('connection', function (ConnectionInterface $conn) use ($that) {
            $that->emit('connection', array($conn));
        });
        $server->on('error', function (\Exception $error) use ($that) {
            $that->emit('error', array($error));
        });
    }

    public function getAddress()
    {
        return $this->server->getAddress();
    }

    public function pause()
    {
        $this->server->pause();
    }

    public function resume()
    {
        $this->server->resume();
    }

    public function close()
    {
        $this->server->close();
    }

    
    public static function accept($socket)
    {
        $errno = 0;
        $errstr = '';
        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
            
            
            $errstr = \preg_replace('#.*: #', '', $error);
            $errno = SocketServer::errno($errstr);
        });

        $newSocket = \stream_socket_accept($socket, 0);

        \restore_error_handler();

        if (false === $newSocket) {
            throw new \RuntimeException(
                'Unable to accept new connection: ' . $errstr . self::errconst($errno),
                $errno
            );
        }

        return $newSocket;
    }

    
    public static function errno($errstr)
    {
        
        $strerror = \function_exists('socket_strerror') ? 'socket_strerror' : (\function_exists('posix_strerror') ? 'posix_strerror' : (\function_exists('pcntl_strerror') ? 'pcntl_strerror' : null));
        if ($strerror !== null) {
            assert(\is_string($strerror) && \is_callable($strerror));

            
            
            
            foreach (\get_defined_constants(false) as $name => $value) {
                if (\is_int($value) && (\strpos($name, 'SOCKET_E') === 0 || \strpos($name, 'PCNTL_E') === 0) && $strerror($value) === $errstr) {
                    return $value;
                }
            }

            
            
            for ($errno = 1, $max = \defined('MAX_ERRNO') ? \MAX_ERRNO : 4095; $errno <= $max; ++$errno) {
                if ($strerror($errno) === $errstr) {
                    return $errno;
                }
            }
        }

        
        return 0;
    }

    
    public static function errconst($errno)
    {
        
        
        
        foreach (\get_defined_constants(false) as $name => $value) {
            if ($value === $errno && (\strpos($name, 'SOCKET_E') === 0 || \strpos($name, 'PCNTL_E') === 0)) {
                return ' (' . \substr($name, \strpos($name, '_') + 1) . ')';
            }
        }

        
        return '';
    }
}
