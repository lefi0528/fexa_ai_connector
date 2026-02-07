<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;


final class UdpTransportExecutor implements ExecutorInterface
{
    private $nameserver;
    private $loop;
    private $parser;
    private $dumper;

    
    private $maxPacketSize = 512;

    
    public function __construct($nameserver, $loop = null)
    {
        if (\strpos($nameserver, '[') === false && \substr_count($nameserver, ':') >= 2 && \strpos($nameserver, '://') === false) {
            
            $nameserver = '[' . $nameserver . ']';
        }

        $parts = \parse_url((\strpos($nameserver, '://') === false ? 'udp://' : '') . $nameserver);
        if (!isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'udp' || @\inet_pton(\trim($parts['host'], '[]')) === false) {
            throw new \InvalidArgumentException('Invalid nameserver address given');
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->nameserver = 'udp://' . $parts['host'] . ':' . (isset($parts['port']) ? $parts['port'] : 53);
        $this->loop = $loop ?: Loop::get();
        $this->parser = new Parser();
        $this->dumper = new BinaryDumper();
    }

    public function query(Query $query)
    {
        $request = Message::createRequestForQuery($query);

        $queryData = $this->dumper->toBinary($request);
        if (isset($queryData[$this->maxPacketSize])) {
            return \React\Promise\reject(new \RuntimeException(
                'DNS query for ' . $query->describe() . ' failed: Query too large for UDP transport',
                \defined('SOCKET_EMSGSIZE') ? \SOCKET_EMSGSIZE : 90
            ));
        }

        
        $errno = 0;
        $errstr = '';
        $socket = @\stream_socket_client($this->nameserver, $errno, $errstr, 0);
        if ($socket === false) {
            return \React\Promise\reject(new \RuntimeException(
                'DNS query for ' . $query->describe() . ' failed: Unable to connect to DNS server ' . $this->nameserver . ' ('  . $errstr . ')',
                $errno
            ));
        }

        
        \stream_set_blocking($socket, false);

        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
            
            
            
            
            \preg_match('/errno=(\d+) (.+)/', $error, $m);
            $errno = isset($m[1]) ? (int) $m[1] : 0;
            $errstr = isset($m[2]) ? $m[2] : $error;
        });

        $written = \fwrite($socket, $queryData);

        \restore_error_handler();

        if ($written !== \strlen($queryData)) {
            return \React\Promise\reject(new \RuntimeException(
                'DNS query for ' . $query->describe() . ' failed: Unable to send query to DNS server ' . $this->nameserver . ' ('  . $errstr . ')',
                $errno
            ));
        }

        $loop = $this->loop;
        $deferred = new Deferred(function () use ($loop, $socket, $query) {
            
            $loop->removeReadStream($socket);
            \fclose($socket);

            throw new CancellationException('DNS query for ' . $query->describe() . ' has been cancelled');
        });

        $max = $this->maxPacketSize;
        $parser = $this->parser;
        $nameserver = $this->nameserver;
        $loop->addReadStream($socket, function ($socket) use ($loop, $deferred, $query, $parser, $request, $max, $nameserver) {
            
            
            $data = @\fread($socket, $max);
            if ($data === false) {
                return;
            }

            try {
                $response = $parser->parseMessage($data);
            } catch (\Exception $e) {
                
                
                return;
            }

            
            
            if ($response->id !== $request->id) {
                return;
            }

            
            $loop->removeReadStream($socket);
            \fclose($socket);

            if ($response->tc) {
                $deferred->reject(new \RuntimeException(
                    'DNS query for ' . $query->describe() . ' failed: The DNS server ' . $nameserver . ' returned a truncated result for a UDP query',
                    \defined('SOCKET_EMSGSIZE') ? \SOCKET_EMSGSIZE : 90
                ));
                return;
            }

            $deferred->resolve($response);
        });

        return $deferred->promise();
    }
}
