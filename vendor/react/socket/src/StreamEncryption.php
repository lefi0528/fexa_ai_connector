<?php

namespace React\Socket;

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use RuntimeException;
use UnexpectedValueException;


class StreamEncryption
{
    private $loop;
    private $method;
    private $server;

    public function __construct(LoopInterface $loop, $server = true)
    {
        $this->loop = $loop;
        $this->server = $server;

        
        
        
        
        
        if ($server) {
            $this->method = \STREAM_CRYPTO_METHOD_TLS_SERVER;

            if (\PHP_VERSION_ID < 70200 && \PHP_VERSION_ID >= 50600) {
                $this->method |= \STREAM_CRYPTO_METHOD_TLSv1_0_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | \STREAM_CRYPTO_METHOD_TLSv1_2_SERVER; 
            }
        } else {
            $this->method = \STREAM_CRYPTO_METHOD_TLS_CLIENT;

            if (\PHP_VERSION_ID < 70200 && \PHP_VERSION_ID >= 50600) {
                $this->method |= \STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT | \STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | \STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT; 
            }
        }
    }

    
    public function enable(Connection $stream)
    {
        return $this->toggle($stream, true);
    }

    
    public function toggle(Connection $stream, $toggle)
    {
        
        $stream->pause();

        

        
        $deferred = new Deferred(function () {
            throw new \RuntimeException();
        });

        
        $socket = $stream->stream;

        
        $method = $this->method;
        $context = \stream_context_get_options($socket);
        if (isset($context['ssl']['crypto_method'])) {
            $method = $context['ssl']['crypto_method'];
        }

        $that = $this;
        $toggleCrypto = function () use ($socket, $deferred, $toggle, $method, $that) {
            $that->toggleCrypto($socket, $deferred, $toggle, $method);
        };

        $this->loop->addReadStream($socket, $toggleCrypto);

        if (!$this->server) {
            $toggleCrypto();
        }

        $loop = $this->loop;

        return $deferred->promise()->then(function () use ($stream, $socket, $loop, $toggle) {
            $loop->removeReadStream($socket);

            $stream->encryptionEnabled = $toggle;
            $stream->resume();

            return $stream;
        }, function($error) use ($stream, $socket, $loop) {
            $loop->removeReadStream($socket);
            $stream->resume();
            throw $error;
        });
    }

    
    public function toggleCrypto($socket, Deferred $deferred, $toggle, $method)
    {
        $error = null;
        \set_error_handler(function ($_, $errstr) use (&$error) {
            $error = \str_replace(array("\r", "\n"), ' ', $errstr);

            
            if (($pos = \strpos($error, "): ")) !== false) {
                $error = \substr($error, $pos + 3);
            }
        });

        $result = \stream_socket_enable_crypto($socket, $toggle, $method);

        \restore_error_handler();

        if (true === $result) {
            $deferred->resolve(null);
        } else if (false === $result) {
            
            
            $d = $deferred;
            $deferred = null;

            if (\feof($socket) || $error === null) {
                
                $d->reject(new \UnexpectedValueException(
                    'Connection lost during TLS handshake (ECONNRESET)',
                    \defined('SOCKET_ECONNRESET') ? \SOCKET_ECONNRESET : 104
                ));
            } else {
                
                $d->reject(new \UnexpectedValueException(
                    $error
                ));
            }
        } else {
            
        }
    }
}
