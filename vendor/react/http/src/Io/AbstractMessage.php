<?php

namespace React\Http\Io;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;


abstract class AbstractMessage implements MessageInterface
{
    
    const REGEX_HEADERS = '/^([^()<>@,;:\\\"\/\[\]?={}\x00-\x20\x7F]++):[\x20\x09]*+((?:[\x20\x09]*+[\x21-\x7E\x80-\xFF]++)*+)[\x20\x09]*+[\r]?+\n/m';

    
    private $headers = array();

    
    private $headerNamesLowerCase = array();

    
    private $protocolVersion;

    
    private $body;

    
    protected function __construct($protocolVersion, array $headers, StreamInterface $body)
    {
        foreach ($headers as $name => $value) {
            if ($value !== array()) {
                if (\is_array($value)) {
                    foreach ($value as &$one) {
                        $one = (string) $one;
                    }
                } else {
                    $value = array((string) $value);
                }

                $lower = \strtolower($name);
                if (isset($this->headerNamesLowerCase[$lower])) {
                    $value = \array_merge($this->headers[$this->headerNamesLowerCase[$lower]], $value);
                    unset($this->headers[$this->headerNamesLowerCase[$lower]]);
                }

                $this->headers[$name] = $value;
                $this->headerNamesLowerCase[$lower] = $name;
            }
        }

        $this->protocolVersion = (string) $protocolVersion;
        $this->body = $body;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        if ((string) $version === $this->protocolVersion) {
            return $this;
        }

        $message = clone $this;
        $message->protocolVersion = (string) $version;

        return $message;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        return isset($this->headerNamesLowerCase[\strtolower($name)]);
    }

    public function getHeader($name)
    {
        $lower = \strtolower($name);
        return isset($this->headerNamesLowerCase[$lower]) ? $this->headers[$this->headerNamesLowerCase[$lower]] : array();
    }

    public function getHeaderLine($name)
    {
        return \implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        if ($value === array()) {
            return $this->withoutHeader($name);
        } elseif (\is_array($value)) {
            foreach ($value as &$one) {
                $one = (string) $one;
            }
        } else {
            $value = array((string) $value);
        }

        $lower = \strtolower($name);
        if (isset($this->headerNamesLowerCase[$lower]) && $this->headerNamesLowerCase[$lower] === (string) $name && $this->headers[$this->headerNamesLowerCase[$lower]] === $value) {
            return $this;
        }

        $message = clone $this;
        if (isset($message->headerNamesLowerCase[$lower])) {
            unset($message->headers[$message->headerNamesLowerCase[$lower]]);
        }

        $message->headers[$name] = $value;
        $message->headerNamesLowerCase[$lower] = $name;

        return $message;
    }

    public function withAddedHeader($name, $value)
    {
        if ($value === array()) {
            return $this;
        }

        return $this->withHeader($name, \array_merge($this->getHeader($name), \is_array($value) ? $value : array($value)));
    }

    public function withoutHeader($name)
    {
        $lower = \strtolower($name);
        if (!isset($this->headerNamesLowerCase[$lower])) {
            return $this;
        }

        $message = clone $this;
        unset($message->headers[$message->headerNamesLowerCase[$lower]], $message->headerNamesLowerCase[$lower]);

        return $message;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        if ($body === $this->body) {
            return $this;
        }

        $message = clone $this;
        $message->body = $body;

        return $message;
    }
}
