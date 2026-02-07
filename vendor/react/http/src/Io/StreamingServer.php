<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Promise;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;


final class StreamingServer extends EventEmitter
{
    private $callback;
    private $parser;

    
    private $clock;

    
    public function __construct(LoopInterface $loop, $requestHandler)
    {
        if (!\is_callable($requestHandler)) {
            throw new \InvalidArgumentException('Invalid request handler given');
        }

        $this->callback = $requestHandler;
        $this->clock = new Clock($loop);
        $this->parser = new RequestHeaderParser($this->clock);

        $that = $this;
        $this->parser->on('headers', function (ServerRequestInterface $request, ConnectionInterface $conn) use ($that) {
            $that->handleRequest($conn, $request);
        });

        $this->parser->on('error', function(\Exception $e, ConnectionInterface $conn) use ($that) {
            $that->emit('error', array($e));

            
            $that->writeError(
                $conn,
                $e->getCode() !== 0 ? $e->getCode() : Response::STATUS_BAD_REQUEST,
                new ServerRequest('GET', '/')
            );
        });
    }

    
    public function listen(ServerInterface $socket)
    {
        $socket->on('connection', array($this->parser, 'handle'));
    }

    
    public function handleRequest(ConnectionInterface $conn, ServerRequestInterface $request)
    {
        if ($request->getProtocolVersion() !== '1.0' && '100-continue' === \strtolower($request->getHeaderLine('Expect'))) {
            $conn->write("HTTP/1.1 100 Continue\r\n\r\n");
        }

        
        $callback = $this->callback;
        try {
            $response = $callback($request);
        } catch (\Exception $error) {
            
            $response = Promise\reject($error);
        } catch (\Throwable $error) { 
            
            $response = Promise\reject($error); 
        }

        
        $connectionOnCloseResponseCancelerHandler = function () {};
        if ($response instanceof PromiseInterface && \method_exists($response, 'cancel')) {
            $connectionOnCloseResponseCanceler = function () use ($response) {
                $response->cancel();
            };
            $connectionOnCloseResponseCancelerHandler = function () use ($connectionOnCloseResponseCanceler, $conn) {
                if ($connectionOnCloseResponseCanceler !== null) {
                    $conn->removeListener('close', $connectionOnCloseResponseCanceler);
                }
            };
            $conn->on('close', $connectionOnCloseResponseCanceler);
        }

        
        if ($response instanceof ResponseInterface) {
            return $this->handleResponse($conn, $request, $response);
        }

        
        if (!$response instanceof PromiseInterface) {
            $response = Promise\resolve($response);
        }

        $that = $this;
        $response->then(
            function ($response) use ($that, $conn, $request) {
                if (!$response instanceof ResponseInterface) {
                    $message = 'The response callback is expected to resolve with an object implementing Psr\Http\Message\ResponseInterface, but resolved with "%s" instead.';
                    $message = \sprintf($message, \is_object($response) ? \get_class($response) : \gettype($response));
                    $exception = new \RuntimeException($message);

                    $that->emit('error', array($exception));
                    return $that->writeError($conn, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
                }
                $that->handleResponse($conn, $request, $response);
            },
            function ($error) use ($that, $conn, $request) {
                $message = 'The response callback is expected to resolve with an object implementing Psr\Http\Message\ResponseInterface, but rejected with "%s" instead.';
                $message = \sprintf($message, \is_object($error) ? \get_class($error) : \gettype($error));

                $previous = null;

                if ($error instanceof \Throwable || $error instanceof \Exception) {
                    $previous = $error;
                }

                $exception = new \RuntimeException($message, 0, $previous);

                $that->emit('error', array($exception));
                return $that->writeError($conn, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
            }
        )->then($connectionOnCloseResponseCancelerHandler, $connectionOnCloseResponseCancelerHandler);
    }

    
    public function writeError(ConnectionInterface $conn, $code, ServerRequestInterface $request)
    {
        $response = new Response(
            $code,
            array(
                'Content-Type' => 'text/plain',
                'Connection' => 'close' 
            ),
            'Error ' . $code
        );

        
        $reason = $response->getReasonPhrase();
        if ($reason !== '') {
            $body = $response->getBody();
            $body->seek(0, SEEK_END);
            $body->write(': ' . $reason);
        }

        $this->handleResponse($conn, $request, $response);
    }


    
    public function handleResponse(ConnectionInterface $connection, ServerRequestInterface $request, ResponseInterface $response)
    {
        
        $body = $response->getBody();
        if (!$connection->isWritable()) {
            $body->close();
            return;
        }

        $code = $response->getStatusCode();
        $method = $request->getMethod();

        
        $version = $request->getProtocolVersion();
        $response = $response->withProtocolVersion($version);

        
        if (!$response->hasHeader('Server')) {
            $response = $response->withHeader('Server', 'ReactPHP/1');
        } elseif ($response->getHeaderLine('Server') === ''){
            $response = $response->withoutHeader('Server');
        }

        
        if (!$response->hasHeader('Date')) {
            
            $response = $response->withHeader('Date', gmdate('D, d M Y H:i:s', (int) $this->clock->now()) . ' GMT');
        } elseif ($response->getHeaderLine('Date') === ''){
            $response = $response->withoutHeader('Date');
        }

        
        $chunked = false;
        if (($method === 'CONNECT' && $code >= 200 && $code < 300) || ($code >= 100 && $code < 200) || $code === Response::STATUS_NO_CONTENT) {
            
            $response = $response->withoutHeader('Content-Length');
        } elseif ($method === 'HEAD' && $response->hasHeader('Content-Length')) {
            
        } elseif ($code === Response::STATUS_NOT_MODIFIED && ($response->hasHeader('Content-Length') || $body->getSize() === 0)) {
            
        } elseif ($body->getSize() !== null) {
            
            $response = $response->withHeader('Content-Length', (string)$body->getSize());
        } elseif (!$response->hasHeader('Content-Length') && $version === '1.1') {
            
            $chunked = true;
        }

        
        if ($chunked) {
            $response = $response->withHeader('Transfer-Encoding', 'chunked');
        } else {
            
            $response = $response->withoutHeader('Transfer-Encoding');
        }

        
        $persist = false;
        if ($code === Response::STATUS_SWITCHING_PROTOCOLS) {
            
            
            
            $response = $response->withHeader('Connection', 'upgrade');
        } elseif (\strtolower($request->getHeaderLine('Connection')) === 'close' || \strtolower($response->getHeaderLine('Connection')) === 'close') {
            
            $response = $response->withHeader('Connection', 'close');
        } elseif ($version === '1.1') {
            
            $persist = true;
        } elseif (strtolower($request->getHeaderLine('Connection')) === 'keep-alive') {
            
            $persist = true;
            $response = $response->withHeader('Connection', 'keep-alive');
        } else {
            
            $response = $response->withoutHeader('Connection');
        }

        
        
        if (($code === Response::STATUS_SWITCHING_PROTOCOLS || ($method === 'CONNECT' && $code >= 200 && $code < 300)) && $body instanceof HttpBodyStream && $body->input instanceof WritableStreamInterface) {
            if ($request->getBody()->isReadable()) {
                
                $request->getBody()->on('close', function () use ($connection, $body) {
                    if ($body->input->isWritable()) {
                        $connection->pipe($body->input);
                        $connection->resume();
                    }
                });
            } elseif ($body->input->isWritable()) {
                
                $connection->pipe($body->input);
                $connection->resume();
            }
        }

        
        $expected = 0;
        $headers = "HTTP/" . $version . " " . $code . " " . $response->getReasonPhrase() . "\r\n";
        foreach ($response->getHeaders() as $name => $values) {
            if (\strpos($name, ':') !== false) {
                $expected = -1;
                break;
            }
            foreach ($values as $value) {
                $headers .= $name . ": " . $value . "\r\n";
                ++$expected;
            }
        }

        
        if ($code < 100 || $code > 999 || \substr_count($headers, "\n") !== ($expected + 1) || (\PHP_VERSION_ID >= 50400 ? \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers) : \preg_match_all(AbstractMessage::REGEX_HEADERS, $headers, $m)) !== $expected) {
            $this->emit('error', array(new \InvalidArgumentException('Unable to send response with invalid response headers')));
            $this->writeError($connection, Response::STATUS_INTERNAL_SERVER_ERROR, $request);
            return;
        }

        
        
        if ($method === 'HEAD' || ($code >= 100 && $code < 200 && $code !== Response::STATUS_SWITCHING_PROTOCOLS) || $code === Response::STATUS_NO_CONTENT || $code === Response::STATUS_NOT_MODIFIED) {
            $body->close();
            $body = '';
        }

        
        if (!$body instanceof ReadableStreamInterface || !$body->isReadable()) {
            
            if ($body instanceof ReadableStreamInterface && $chunked) {
                $body = "0\r\n\r\n";
            }

            
            $connection->write($headers . "\r\n" . $body);

            
            if ($persist) {
                $this->parser->handle($connection);
            } else {
                $connection->end();
            }
            return;
        }

        $connection->write($headers . "\r\n");

        if ($chunked) {
            $body = new ChunkedEncoder($body);
        }

        
        
        
        $connection->on('close', array($body, 'close'));

        
        if ($persist) {
            $body->pipe($connection, array('end' => false));
            $parser = $this->parser;
            $body->on('end', function () use ($connection, $parser, $body) {
                $connection->removeListener('close', array($body, 'close'));
                $parser->handle($connection);
            });
        } else {
            $body->pipe($connection);
        }
    }
}
