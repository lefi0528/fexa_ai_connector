<?php

namespace React\Http\Io;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Http\Message\ResponseException;
use React\Http\Message\Uri;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;


class Transaction
{
    private $sender;
    private $loop;

    
    private $timeout;

    
    private $followRedirects = true;

    
    private $maxRedirects = 10;

    
    private $obeySuccessCode = true;

    private $streaming = false;

    private $maximumSize = 16777216; 

    public function __construct(Sender $sender, LoopInterface $loop)
    {
        $this->sender = $sender;
        $this->loop = $loop;
    }

    
    public function withOptions(array $options)
    {
        $transaction = clone $this;
        foreach ($options as $name => $value) {
            if (property_exists($transaction, $name)) {
                
                if ($value === null) {
                    $default = new self($this->sender, $this->loop);
                    $value = $default->$name;
                }

                $transaction->$name = $value;
            }
        }

        return $transaction;
    }

    public function send(RequestInterface $request)
    {
        $state = new ClientRequestState();
        $deferred = new Deferred(function () use ($state) {
            if ($state->pending !== null) {
                $state->pending->cancel();
                $state->pending = null;
            }
        });

        
        $timeout = (float)($this->timeout !== null ? $this->timeout : ini_get("default_socket_timeout"));

        $loop = $this->loop;
        $this->next($request, $deferred, $state)->then(
            function (ResponseInterface $response) use ($state, $deferred, $loop, &$timeout) {
                if ($state->timeout !== null) {
                    $loop->cancelTimer($state->timeout);
                    $state->timeout = null;
                }
                $timeout = -1;
                $deferred->resolve($response);
            },
            function ($e) use ($state, $deferred, $loop, &$timeout) {
                if ($state->timeout !== null) {
                    $loop->cancelTimer($state->timeout);
                    $state->timeout = null;
                }
                $timeout = -1;
                $deferred->reject($e);
            }
        );

        if ($timeout < 0) {
            return $deferred->promise();
        }

        $body = $request->getBody();
        if ($body instanceof ReadableStreamInterface && $body->isReadable()) {
            $that = $this;
            $body->on('close', function () use ($that, $deferred, $state, &$timeout) {
                if ($timeout >= 0) {
                    $that->applyTimeout($deferred, $state, $timeout);
                }
            });
        } else {
            $this->applyTimeout($deferred, $state, $timeout);
        }

        return $deferred->promise();
    }

    
    public function applyTimeout(Deferred $deferred, ClientRequestState $state, $timeout)
    {
        $state->timeout = $this->loop->addTimer($timeout, function () use ($timeout, $deferred, $state) {
            $deferred->reject(new \RuntimeException(
                'Request timed out after ' . $timeout . ' seconds'
            ));
            if ($state->pending !== null) {
                $state->pending->cancel();
                $state->pending = null;
            }
        });
    }

    private function next(RequestInterface $request, Deferred $deferred, ClientRequestState $state)
    {
        $this->progress('request', array($request));

        $that = $this;
        ++$state->numRequests;

        $promise = $this->sender->send($request);

        if (!$this->streaming) {
            $promise = $promise->then(function ($response) use ($deferred, $state, $that) {
                return $that->bufferResponse($response, $deferred, $state);
            });
        }

        $state->pending = $promise;

        return $promise->then(
            function (ResponseInterface $response) use ($request, $that, $deferred, $state) {
                return $that->onResponse($response, $request, $deferred, $state);
            }
        );
    }

    
    public function bufferResponse(ResponseInterface $response, Deferred $deferred, ClientRequestState $state)
    {
        $body = $response->getBody();
        $size = $body->getSize();

        if ($size !== null && $size > $this->maximumSize) {
            $body->close();
            return \React\Promise\reject(new \OverflowException(
                'Response body size of ' . $size . ' bytes exceeds maximum of ' . $this->maximumSize . ' bytes',
                \defined('SOCKET_EMSGSIZE') ? \SOCKET_EMSGSIZE : 90
            ));
        }

        
        if (!$body instanceof ReadableStreamInterface) {
            return \React\Promise\resolve($response);
        }

        
        $closer = null;
        $maximumSize = $this->maximumSize;

        return $state->pending = new Promise(function ($resolve, $reject) use ($body, $maximumSize, $response, &$closer) {
            
            $buffer = '';
            $body->on('close', $closer = function () use (&$buffer, $response, $maximumSize, $resolve, $reject) {
                $resolve($response->withBody(new BufferedBody($buffer)));
            });

            
            $body->on('data', function ($data) use (&$buffer, $maximumSize, $body, $closer, $reject) {
                $buffer .= $data;

                
                if (isset($buffer[$maximumSize])) {
                    $buffer = '';
                    assert($closer instanceof \Closure);
                    $body->removeListener('close', $closer);
                    $body->close();

                    $reject(new \OverflowException(
                        'Response body size exceeds maximum of ' . $maximumSize . ' bytes',
                        \defined('SOCKET_EMSGSIZE') ? \SOCKET_EMSGSIZE : 90
                    ));
                }
            });

            
            $body->on('error', function (\Exception $e) use ($reject) {
                $reject(new \RuntimeException(
                    'Error while buffering response body: ' . $e->getMessage(),
                    $e->getCode(),
                    $e
                ));
            });
        }, function () use ($body, &$closer) {
            
            assert($closer instanceof \Closure);
            $body->removeListener('close', $closer);
            $body->close();

            throw new \RuntimeException('Cancelled buffering response body');
        });
    }

    
    public function onResponse(ResponseInterface $response, RequestInterface $request, Deferred $deferred, ClientRequestState $state)
    {
        $this->progress('response', array($response, $request));

        
        
        if ($this->followRedirects && ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) && $response->hasHeader('Location')) {
            return $this->onResponseRedirect($response, $request, $deferred, $state);
        }

        
        if ($this->obeySuccessCode && ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400)) {
            throw new ResponseException($response);
        }

        
        return $response;
    }

    
    private function onResponseRedirect(ResponseInterface $response, RequestInterface $request, Deferred $deferred, ClientRequestState $state)
    {
        
        $location = Uri::resolve($request->getUri(), new Uri($response->getHeaderLine('Location')));

        $request = $this->makeRedirectRequest($request, $location, $response->getStatusCode());
        $this->progress('redirect', array($request));

        if ($state->numRequests >= $this->maxRedirects) {
            throw new \RuntimeException('Maximum number of redirects (' . $this->maxRedirects . ') exceeded');
        }

        return $this->next($request, $deferred, $state);
    }

    
    private function makeRedirectRequest(RequestInterface $request, UriInterface $location, $statusCode)
    {
        
        $originalHost = $request->getUri()->getHost();
        if ($location->getHost() !== $originalHost) {
            $request = $request->withoutHeader('Authorization');
        }

        $request = $request->withoutHeader('Host')->withUri($location);

        if ($statusCode === Response::STATUS_TEMPORARY_REDIRECT || $statusCode === Response::STATUS_PERMANENT_REDIRECT) {
            if ($request->getBody() instanceof ReadableStreamInterface) {
                throw new \RuntimeException('Unable to redirect request with streaming body');
            }
        } else {
            $request = $request
                ->withMethod($request->getMethod() === 'HEAD' ? 'HEAD' : 'GET')
                ->withoutHeader('Content-Type')
                ->withoutHeader('Content-Length')
                ->withBody(new BufferedBody(''));
        }

        return $request;
    }

    private function progress($name, array $args = array())
    {
        return;

        echo $name;

        foreach ($args as $arg) {
            echo ' ';
            if ($arg instanceof ResponseInterface) {
                echo 'HTTP/' . $arg->getProtocolVersion() . ' ' . $arg->getStatusCode() . ' ' . $arg->getReasonPhrase();
            } elseif ($arg instanceof RequestInterface) {
                echo $arg->getMethod() . ' ' . $arg->getRequestTarget() . ' HTTP/' . $arg->getProtocolVersion();
            } else {
                echo $arg;
            }
        }

        echo PHP_EOL;
    }
}
