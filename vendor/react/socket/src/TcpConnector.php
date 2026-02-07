<?php

namespace React\Socket;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise;
use InvalidArgumentException;
use RuntimeException;

final class TcpConnector implements ConnectorInterface
{
    private $loop;
    private $context;

    
    public function __construct($loop = null, array $context = array())
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->loop = $loop ?: Loop::get();
        $this->context = $context;
    }

    public function connect($uri)
    {
        if (\strpos($uri, '://') === false) {
            $uri = 'tcp://' . $uri;
        }

        $parts = \parse_url($uri);
        if (!$parts || !isset($parts['scheme'], $parts['host'], $parts['port']) || $parts['scheme'] !== 'tcp') {
            return Promise\reject(new \InvalidArgumentException(
                'Given URI "' . $uri . '" is invalid (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            ));
        }

        $ip = \trim($parts['host'], '[]');
        if (@\inet_pton($ip) === false) {
            return Promise\reject(new \InvalidArgumentException(
                'Given URI "' . $uri . '" does not contain a valid host IP (EINVAL)',
                \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : (\defined('PCNTL_EINVAL') ? \PCNTL_EINVAL : 22)
            ));
        }

        
        $context = array(
            'socket' => $this->context
        );

        
        $args = array();
        if (isset($parts['query'])) {
            \parse_str($parts['query'], $args);
        }

        
        
        
        
        
        if (isset($args['hostname'])) {
            $context['ssl'] = array(
                'SNI_enabled' => true,
                'peer_name' => $args['hostname']
            );

            
            
            
            
            if (\PHP_VERSION_ID < 50600) {
                $context['ssl'] += array(
                    'SNI_server_name' => $args['hostname'],
                    'CN_match' => $args['hostname']
                );
            }
            
        }

        
        
        $remote = 'tcp://' . $parts['host'] . ':' . $parts['port'];

        $stream = @\stream_socket_client(
            $remote,
            $errno,
            $errstr,
            0,
            \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT,
            \stream_context_create($context)
        );

        if (false === $stream) {
            return Promise\reject(new \RuntimeException(
                'Connection to ' . $uri . ' failed: ' . $errstr . SocketServer::errconst($errno),
                $errno
            ));
        }

        
        $loop = $this->loop;
        return new Promise\Promise(function ($resolve, $reject) use ($loop, $stream, $uri) {
            $loop->addWriteStream($stream, function ($stream) use ($loop, $resolve, $reject, $uri) {
                $loop->removeWriteStream($stream);

                
                
                if (false === \stream_socket_get_name($stream, true)) {
                    
                    
                    if (\function_exists('socket_import_stream')) {
                        
                        $socket = \socket_import_stream($stream);
                        $errno = \socket_get_option($socket, \SOL_SOCKET, \SO_ERROR);
                        $errstr = \socket_strerror($errno);
                    } elseif (\PHP_OS === 'Linux') {
                        
                        
                        
                        $errno = 0;
                        $errstr = '';
                        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
                            
                            
                            \preg_match('/errno=(\d+) (.+)/', $error, $m);
                            $errno = isset($m[1]) ? (int) $m[1] : 0;
                            $errstr = isset($m[2]) ? $m[2] : $error;
                        });

                        \fwrite($stream, \PHP_EOL);

                        \restore_error_handler();
                    } else {
                        
                        $errno = \defined('SOCKET_ECONNREFUSED') ? \SOCKET_ECONNREFUSED : 111;
                        $errstr = 'Connection refused?';
                    }
                    

                    \fclose($stream);
                    $reject(new \RuntimeException(
                        'Connection to ' . $uri . ' failed: ' . $errstr . SocketServer::errconst($errno),
                        $errno
                    ));
                } else {
                    $resolve(new Connection($stream, $loop));
                }
            });
        }, function () use ($loop, $stream, $uri) {
            $loop->removeWriteStream($stream);
            \fclose($stream);

            
            
            if (\PHP_VERSION_ID < 50400 && \is_resource($stream)) {
                \fclose($stream);
            }
            

            throw new \RuntimeException(
                'Connection to ' . $uri . ' cancelled during TCP/IP handshake (ECONNABORTED)',
                \defined('SOCKET_ECONNABORTED') ? \SOCKET_ECONNABORTED : 103
            );
        });
    }
}
