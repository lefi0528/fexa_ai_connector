<?php

namespace React\Http\Io;

use Evenement\EventEmitter;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Socket\ConnectionInterface;
use Exception;


class RequestHeaderParser extends EventEmitter
{
    private $maxSize = 8192;

    
    private $clock;

    
    private $connectionParams = array();

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function handle(ConnectionInterface $conn)
    {
        $buffer = '';
        $maxSize = $this->maxSize;
        $that = $this;
        $conn->on('data', $fn = function ($data) use (&$buffer, &$fn, $conn, $maxSize, $that) {
            
            $buffer .= $data;
            $endOfHeader = \strpos($buffer, "\r\n\r\n");

            
            if ($endOfHeader > $maxSize || ($endOfHeader === false && isset($buffer[$maxSize]))) {
                $conn->removeListener('data', $fn);
                $fn = null;

                $that->emit('error', array(
                    new \OverflowException("Maximum header size of {$maxSize} exceeded.", Response::STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE),
                    $conn
                ));
                return;
            }

            
            if ($endOfHeader === false) {
                return;
            }

            
            $conn->removeListener('data', $fn);
            $fn = null;

            try {
                $request = $that->parseRequest(
                    (string)\substr($buffer, 0, $endOfHeader + 2),
                    $conn
                );
            } catch (Exception $exception) {
                $buffer = '';
                $that->emit('error', array(
                    $exception,
                    $conn
                ));
                return;
            }

            $contentLength = 0;
            if ($request->hasHeader('Transfer-Encoding')) {
                $contentLength = null;
            } elseif ($request->hasHeader('Content-Length')) {
                $contentLength = (int)$request->getHeaderLine('Content-Length');
            }

            if ($contentLength === 0) {
                
                $stream = new EmptyBodyStream();
                $request = $request->withBody($stream);
            } else {
                
                $stream = new CloseProtectionStream($conn);
                if ($contentLength !== null) {
                    $stream = new LengthLimitedStream($stream, $contentLength);
                } else {
                    $stream = new ChunkedDecoder($stream);
                }

                $request = $request->withBody(new HttpBodyStream($stream, $contentLength));
            }

            $bodyBuffer = isset($buffer[$endOfHeader + 4]) ? \substr($buffer, $endOfHeader + 4) : '';
            $buffer = '';
            $that->emit('headers', array($request, $conn));

            if ($bodyBuffer !== '') {
                $conn->emit('data', array($bodyBuffer));
            }

            
            if ($contentLength === 0) {
                $stream->emit('end');
                $stream->close();
            }
        });
    }

    
    public function parseRequest($headers, ConnectionInterface $connection)
    {
        
        $cid = \PHP_VERSION_ID < 70200 ? \spl_object_hash($connection) : \spl_object_id($connection);
        if (isset($this->connectionParams[$cid])) {
            $serverParams = $this->connectionParams[$cid];
        } else {
            
            $serverParams = array();

            
            $localSocketUri = $connection->getLocalAddress();
            $localParts = $localSocketUri === null ? array() : \parse_url($localSocketUri);
            if (isset($localParts['scheme']) && $localParts['scheme'] === 'tls') {
                $serverParams['HTTPS'] = 'on';
            }

            
            
            
            if ($localSocketUri !== null && isset($localParts['host'], $localParts['port'])) {
                $serverParams['SERVER_ADDR'] = $localParts['host'];
                $serverParams['SERVER_PORT'] = $localParts['port'];
            }

            
            
            $remoteSocketUri = $connection->getRemoteAddress();
            if ($remoteSocketUri !== null) {
                $remoteAddress = \parse_url($remoteSocketUri);
                $serverParams['REMOTE_ADDR'] = $remoteAddress['host'];
                $serverParams['REMOTE_PORT'] = $remoteAddress['port'];
            }

            
            $this->connectionParams[$cid] = $serverParams;
            $params =& $this->connectionParams;
            $connection->on('close', function () use (&$params, $cid) {
                assert(\is_array($params));
                unset($params[$cid]);
            });
        }

        
        
        $serverParams['REQUEST_TIME'] = (int) ($now = $this->clock->now());
        $serverParams['REQUEST_TIME_FLOAT'] = $now;

        return ServerRequest::parseMessage($headers, $serverParams);
    }
}
