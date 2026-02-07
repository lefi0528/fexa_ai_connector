<?php

namespace React\Socket;


class FixedUriConnector implements ConnectorInterface
{
    private $uri;
    private $connector;

    
    public function __construct($uri, ConnectorInterface $connector)
    {
        $this->uri = $uri;
        $this->connector = $connector;
    }

    public function connect($_)
    {
        return $this->connector->connect($this->uri);
    }
}
