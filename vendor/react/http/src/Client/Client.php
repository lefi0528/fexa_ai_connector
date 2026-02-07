<?php

namespace React\Http\Client;

use Psr\Http\Message\RequestInterface;
use React\Http\Io\ClientConnectionManager;
use React\Http\Io\ClientRequestStream;


class Client
{
    
    private $connectionManager;

    public function __construct(ClientConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    
    public function request(RequestInterface $request)
    {
        return new ClientRequestStream($this->connectionManager, $request);
    }
}
