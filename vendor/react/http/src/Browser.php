<?php

namespace React\Http;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\Io\Sender;
use React\Http\Io\Transaction;
use React\Http\Message\Request;
use React\Http\Message\Uri;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use React\Stream\ReadableStreamInterface;
use InvalidArgumentException;


class Browser
{
    private $transaction;
    private $baseUrl;
    private $protocolVersion = '1.1';
    private $defaultHeaders = array(
        'User-Agent' => 'ReactPHP/1'
    );

    
    public function __construct($connector = null, $loop = null)
    {
        
        if (($connector instanceof LoopInterface || $connector === null) && ($loop instanceof ConnectorInterface || $loop === null)) {
            $swap = $loop;
            $loop = $connector;
            $connector = $swap;
        }

        if (($connector !== null && !$connector instanceof ConnectorInterface) || ($loop !== null && !$loop instanceof LoopInterface)) {
            throw new \InvalidArgumentException('Expected "?ConnectorInterface $connector" and "?LoopInterface $loop" arguments');
        }

        $loop = $loop ?: Loop::get();
        $this->transaction = new Transaction(
            Sender::createFromLoop($loop, $connector ?: new Connector(array(), $loop)),
            $loop
        );
    }

    
    public function get($url, array $headers = array())
    {
        return $this->requestMayBeStreaming('GET', $url, $headers);
    }

    
    public function post($url, array $headers = array(), $body = '')
    {
        return $this->requestMayBeStreaming('POST', $url, $headers, $body);
    }

    
    public function head($url, array $headers = array())
    {
        return $this->requestMayBeStreaming('HEAD', $url, $headers);
    }

    
    public function patch($url, array $headers = array(), $body = '')
    {
        return $this->requestMayBeStreaming('PATCH', $url , $headers, $body);
    }

    
    public function put($url, array $headers = array(), $body = '')
    {
        return $this->requestMayBeStreaming('PUT', $url, $headers, $body);
    }

    
    public function delete($url, array $headers = array(), $body = '')
    {
        return $this->requestMayBeStreaming('DELETE', $url, $headers, $body);
    }

    
    public function request($method, $url, array $headers = array(), $body = '')
    {
        return $this->withOptions(array('streaming' => false))->requestMayBeStreaming($method, $url, $headers, $body);
    }

    
    public function requestStreaming($method, $url, $headers = array(), $body = '')
    {
        return $this->withOptions(array('streaming' => true))->requestMayBeStreaming($method, $url, $headers, $body);
    }

    
    public function withTimeout($timeout)
    {
        if ($timeout === true) {
            $timeout = null;
        } elseif ($timeout === false) {
            $timeout = -1;
        } elseif ($timeout < 0) {
            $timeout = 0;
        }

        return $this->withOptions(array(
            'timeout' => $timeout,
        ));
    }

    
    public function withFollowRedirects($followRedirects)
    {
        return $this->withOptions(array(
            'followRedirects' => $followRedirects !== false,
            'maxRedirects' => \is_bool($followRedirects) ? null : $followRedirects
        ));
    }

    
    public function withRejectErrorResponse($obeySuccessCode)
    {
        return $this->withOptions(array(
            'obeySuccessCode' => $obeySuccessCode,
        ));
    }

    
    public function withBase($baseUrl)
    {
        $browser = clone $this;
        if ($baseUrl === null) {
            $browser->baseUrl = null;
            return $browser;
        }

        $browser->baseUrl = new Uri($baseUrl);
        if (!\in_array($browser->baseUrl->getScheme(), array('http', 'https')) || $browser->baseUrl->getHost() === '') {
            throw new \InvalidArgumentException('Base URL must be absolute');
        }

        return $browser;
    }

    
    public function withProtocolVersion($protocolVersion)
    {
        if (!\in_array($protocolVersion, array('1.0', '1.1'), true)) {
            throw new InvalidArgumentException('Invalid HTTP protocol version, must be one of "1.1" or "1.0"');
        }

        $browser = clone $this;
        $browser->protocolVersion = (string) $protocolVersion;

        return $browser;
    }

    
    public function withResponseBuffer($maximumSize)
    {
        return $this->withOptions(array(
            'maximumSize' => $maximumSize
        ));
    }

    
    public function withHeader($header, $value)
    {
        $browser = $this->withoutHeader($header);
        $browser->defaultHeaders[$header] = $value;

        return $browser;
    }

    
    public function withoutHeader($header)
    {
        $browser = clone $this;

        
        foreach (\array_keys($browser->defaultHeaders) as $key) {
            if (\strcasecmp($key, $header) === 0) {
                unset($browser->defaultHeaders[$key]);
                break;
            }
        }

        return $browser;
    }

    
    private function withOptions(array $options)
    {
        $browser = clone $this;
        $browser->transaction = $this->transaction->withOptions($options);

        return $browser;
    }

    
    private function requestMayBeStreaming($method, $url, array $headers = array(), $body = '')
    {
        if ($this->baseUrl !== null) {
            
            $url = Uri::resolve($this->baseUrl, new Uri($url));
        }

        foreach ($this->defaultHeaders as $key => $value) {
            $explicitHeaderExists = false;
            foreach (\array_keys($headers) as $headerKey) {
                if (\strcasecmp($headerKey, $key) === 0) {
                    $explicitHeaderExists = true;
                    break;
                }
            }
            if (!$explicitHeaderExists) {
                $headers[$key] = $value;
            }
        }

        return $this->transaction->send(
            new Request($method, $url, $headers, $body, $this->protocolVersion)
        );
    }
}
