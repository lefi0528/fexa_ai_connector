<?php

namespace React\Http\Message;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;


final class ResponseException extends RuntimeException
{
    private $response;

    public function __construct(ResponseInterface $response, $message = null, $code = null, $previous = null)
    {
        if ($message === null) {
            $message = 'HTTP status code ' . $response->getStatusCode() . ' (' . $response->getReasonPhrase() . ')';
        }
        if ($code === null) {
            $code = $response->getStatusCode();
        }
        parent::__construct($message, $code, $previous);

        $this->response = $response;
    }

    
    public function getResponse()
    {
        return $this->response;
    }
}
