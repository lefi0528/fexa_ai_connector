<?php

namespace React\Http\Io;

use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;


class ClientConnectionManager
{
    
    private $connector;

    
    private $loop;

    
    private $idleUris = array();

    
    private $idleConnections = array();

    
    private $idleTimers = array();

    
    private $idleStreamHandlers = array();

    
    private $maximumTimeToKeepAliveIdleConnection = 0.001;

    public function __construct(ConnectorInterface $connector, LoopInterface $loop)
    {
        $this->connector = $connector;
        $this->loop = $loop;
    }

    
    public function connect(UriInterface $uri)
    {
        $scheme = $uri->getScheme();
        if ($scheme !== 'https' && $scheme !== 'http') {
            return \React\Promise\reject(new \InvalidArgumentException(
                'Invalid request URL given'
            ));
        }

        $port = $uri->getPort();
        if ($port === null) {
            $port = $scheme === 'https' ? 443 : 80;
        }
        $uri = ($scheme === 'https' ? 'tls://' : '') . $uri->getHost() . ':' . $port;

        
        foreach ($this->idleConnections as $id => $connection) {
            if ($this->idleUris[$id] === $uri) {
                assert($this->idleStreamHandlers[$id] instanceof \Closure);
                $connection->removeListener('close', $this->idleStreamHandlers[$id]);
                $connection->removeListener('data', $this->idleStreamHandlers[$id]);
                $connection->removeListener('error', $this->idleStreamHandlers[$id]);

                assert($this->idleTimers[$id] instanceof TimerInterface);
                $this->loop->cancelTimer($this->idleTimers[$id]);
                unset($this->idleUris[$id], $this->idleConnections[$id], $this->idleTimers[$id], $this->idleStreamHandlers[$id]);

                return \React\Promise\resolve($connection);
            }
        }

        
        return $this->connector->connect($uri);
    }

    
    public function keepAlive(UriInterface $uri, ConnectionInterface $connection)
    {
        $scheme = $uri->getScheme();
        assert($scheme === 'https' || $scheme === 'http');

        $port = $uri->getPort();
        if ($port === null) {
            $port = $scheme === 'https' ? 443 : 80;
        }

        $this->idleUris[] = ($scheme === 'https' ? 'tls://' : '') . $uri->getHost() . ':' . $port;
        $this->idleConnections[] = $connection;

        $that = $this;
        $cleanUp = function () use ($connection, $that) {
            
            $that->cleanUpConnection($connection);
        };

        
        $this->idleTimers[] = $this->loop->addTimer($this->maximumTimeToKeepAliveIdleConnection, $cleanUp);

        
        $this->idleStreamHandlers[] = $cleanUp;
        $connection->on('close', $cleanUp);
        $connection->on('data', $cleanUp);
        $connection->on('error', $cleanUp);
    }

    
    public function cleanUpConnection(ConnectionInterface $connection) 
    {
        $id = \array_search($connection, $this->idleConnections, true);
        if ($id === false) {
            return;
        }

        assert(\is_int($id));
        assert($this->idleTimers[$id] instanceof TimerInterface);
        $this->loop->cancelTimer($this->idleTimers[$id]);
        unset($this->idleUris[$id], $this->idleConnections[$id], $this->idleTimers[$id], $this->idleStreamHandlers[$id]);

        $connection->close();
    }
}
