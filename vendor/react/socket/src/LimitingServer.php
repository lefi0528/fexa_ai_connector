<?php

namespace React\Socket;

use Evenement\EventEmitter;
use Exception;
use OverflowException;


class LimitingServer extends EventEmitter implements ServerInterface
{
    private $connections = array();
    private $server;
    private $limit;

    private $pauseOnLimit = false;
    private $autoPaused = false;
    private $manuPaused = false;

    
    public function __construct(ServerInterface $server, $connectionLimit, $pauseOnLimit = false)
    {
        $this->server = $server;
        $this->limit = $connectionLimit;
        if ($connectionLimit !== null) {
            $this->pauseOnLimit = $pauseOnLimit;
        }

        $this->server->on('connection', array($this, 'handleConnection'));
        $this->server->on('error', array($this, 'handleError'));
    }

    
    public function getConnections()
    {
        return $this->connections;
    }

    public function getAddress()
    {
        return $this->server->getAddress();
    }

    public function pause()
    {
        if (!$this->manuPaused) {
            $this->manuPaused = true;

            if (!$this->autoPaused) {
                $this->server->pause();
            }
        }
    }

    public function resume()
    {
        if ($this->manuPaused) {
            $this->manuPaused = false;

            if (!$this->autoPaused) {
                $this->server->resume();
            }
        }
    }

    public function close()
    {
        $this->server->close();
    }

    
    public function handleConnection(ConnectionInterface $connection)
    {
        
        if ($this->limit !== null && \count($this->connections) >= $this->limit) {
            $this->handleError(new \OverflowException('Connection closed because server reached connection limit'));
            $connection->close();
            return;
        }

        $this->connections[] = $connection;
        $that = $this;
        $connection->on('close', function () use ($that, $connection) {
            $that->handleDisconnection($connection);
        });

        
        if ($this->pauseOnLimit && !$this->autoPaused && \count($this->connections) >= $this->limit) {
            $this->autoPaused = true;

            if (!$this->manuPaused) {
                $this->server->pause();
            }
        }

        $this->emit('connection', array($connection));
    }

    
    public function handleDisconnection(ConnectionInterface $connection)
    {
        unset($this->connections[\array_search($connection, $this->connections)]);

        
        if ($this->autoPaused && \count($this->connections) < $this->limit) {
            $this->autoPaused = false;

            if (!$this->manuPaused) {
                $this->server->resume();
            }
        }
    }

    
    public function handleError(\Exception $error)
    {
        $this->emit('error', array($error));
    }
}
