<?php

namespace React\Socket;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use BadMethodCallException;
use UnexpectedValueException;


final class SecureServer extends EventEmitter implements ServerInterface
{
    private $tcp;
    private $encryption;
    private $context;

    
    public function __construct(ServerInterface $tcp, $loop = null, array $context = array())
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        if (!\function_exists('stream_socket_enable_crypto')) {
            throw new \BadMethodCallException('Encryption not supported on your platform (HHVM < 3.8?)'); 
        }

        
        $context += array(
            'passphrase' => ''
        );

        $this->tcp = $tcp;
        $this->encryption = new StreamEncryption($loop ?: Loop::get());
        $this->context = $context;

        $that = $this;
        $this->tcp->on('connection', function ($connection) use ($that) {
            $that->handleConnection($connection);
        });
        $this->tcp->on('error', function ($error) use ($that) {
            $that->emit('error', array($error));
        });
    }

    public function getAddress()
    {
        $address = $this->tcp->getAddress();
        if ($address === null) {
            return null;
        }

        return \str_replace('tcp://' , 'tls://', $address);
    }

    public function pause()
    {
        $this->tcp->pause();
    }

    public function resume()
    {
        $this->tcp->resume();
    }

    public function close()
    {
        return $this->tcp->close();
    }

    
    public function handleConnection(ConnectionInterface $connection)
    {
        if (!$connection instanceof Connection) {
            $this->emit('error', array(new \UnexpectedValueException('Base server does not use internal Connection class exposing stream resource')));
            $connection->close();
            return;
        }

        foreach ($this->context as $name => $value) {
            \stream_context_set_option($connection->stream, 'ssl', $name, $value);
        }

        
        $remote = $connection->getRemoteAddress();
        $that = $this;

        $this->encryption->enable($connection)->then(
            function ($conn) use ($that) {
                $that->emit('connection', array($conn));
            },
            function ($error) use ($that, $connection, $remote) {
                $error = new \RuntimeException(
                    'Connection from ' . $remote . ' failed during TLS handshake: ' . $error->getMessage(),
                    $error->getCode()
                );

                $that->emit('error', array($error));
                $connection->close();
            }
        );
    }
}
