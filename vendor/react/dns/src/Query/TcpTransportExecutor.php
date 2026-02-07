<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;
use React\Dns\Protocol\BinaryDumper;
use React\Dns\Protocol\Parser;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;


class TcpTransportExecutor implements ExecutorInterface
{
    private $nameserver;
    private $loop;
    private $parser;
    private $dumper;

    
    private $socket;

    
    private $pending = array();

    
    private $names = array();

    
    private $idlePeriod = 0.001;

    
    private $idleTimer;

    private $writeBuffer = '';
    private $writePending = false;

    private $readBuffer = '';
    private $readPending = false;

    
    private $readChunk = 0xffff;

    
    public function __construct($nameserver, $loop = null)
    {
        if (\strpos($nameserver, '[') === false && \substr_count($nameserver, ':') >= 2 && \strpos($nameserver, '://') === false) {
            
            $nameserver = '[' . $nameserver . ']';
        }

        $parts = \parse_url((\strpos($nameserver, '://') === false ? 'tcp://' : '') . $nameserver);
        if (!isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'tcp' || @\inet_pton(\trim($parts['host'], '[]')) === false) {
            throw new \InvalidArgumentException('Invalid nameserver address given');
        }

        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #2 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        $this->nameserver = 'tcp://' . $parts['host'] . ':' . (isset($parts['port']) ? $parts['port'] : 53);
        $this->loop = $loop ?: Loop::get();
        $this->parser = new Parser();
        $this->dumper = new BinaryDumper();
    }

    public function query(Query $query)
    {
        $request = Message::createRequestForQuery($query);

        
        while (isset($this->pending[$request->id])) {
            $request->id = \mt_rand(0, 0xffff); 
        }

        $queryData = $this->dumper->toBinary($request);
        $length = \strlen($queryData);
        if ($length > 0xffff) {
            return \React\Promise\reject(new \RuntimeException(
                'DNS query for ' . $query->describe() . ' failed: Query too large for TCP transport'
            ));
        }

        $queryData = \pack('n', $length) . $queryData;

        if ($this->socket === null) {
            
            $socket = @\stream_socket_client($this->nameserver, $errno, $errstr, 0, \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_ASYNC_CONNECT);
            if ($socket === false) {
                return \React\Promise\reject(new \RuntimeException(
                    'DNS query for ' . $query->describe() . ' failed: Unable to connect to DNS server ' . $this->nameserver . ' ('  . $errstr . ')',
                    $errno
                ));
            }

            
            \stream_set_blocking($socket, false);
            if (\function_exists('stream_set_chunk_size')) {
                \stream_set_chunk_size($socket, $this->readChunk); 
            }
            $this->socket = $socket;
        }

        if ($this->idleTimer !== null) {
            $this->loop->cancelTimer($this->idleTimer);
            $this->idleTimer = null;
        }

        
        $this->writeBuffer .= $queryData;
        if (!$this->writePending) {
            $this->writePending = true;
            $this->loop->addWriteStream($this->socket, array($this, 'handleWritable'));
        }

        $names =& $this->names;
        $that = $this;
        $deferred = new Deferred(function () use ($that, &$names, $request) {
            
            $name = $names[$request->id];
            unset($names[$request->id]);
            $that->checkIdle();

            throw new CancellationException('DNS query for ' . $name . ' has been cancelled');
        });

        $this->pending[$request->id] = $deferred;
        $this->names[$request->id] = $query->describe();

        return $deferred->promise();
    }

    
    public function handleWritable()
    {
        if ($this->readPending === false) {
            $name = @\stream_socket_get_name($this->socket, true);
            if ($name === false) {
                
                
                if (\function_exists('socket_import_stream')) {
                    $socket = \socket_import_stream($this->socket);
                    $errno = \socket_get_option($socket, \SOL_SOCKET, \SO_ERROR);
                    $errstr = \socket_strerror($errno);
                } else {
                    $errno = \defined('SOCKET_ECONNREFUSED') ? \SOCKET_ECONNREFUSED : 111;
                    $errstr = 'Connection refused';
                }
                

                $this->closeError('Unable to connect to DNS server ' . $this->nameserver . ' (' . $errstr . ')', $errno);
                return;
            }

            $this->readPending = true;
            $this->loop->addReadStream($this->socket, array($this, 'handleRead'));
        }

        $errno = 0;
        $errstr = '';
        \set_error_handler(function ($_, $error) use (&$errno, &$errstr) {
            
            
            \preg_match('/errno=(\d+) (.+)/', $error, $m);
            $errno = isset($m[1]) ? (int) $m[1] : 0;
            $errstr = isset($m[2]) ? $m[2] : $error;
        });

        $written = \fwrite($this->socket, $this->writeBuffer);

        \restore_error_handler();

        if ($written === false || $written === 0) {
            $this->closeError(
                'Unable to send query to DNS server ' . $this->nameserver . ' (' . $errstr . ')',
                $errno
            );
            return;
        }

        if (isset($this->writeBuffer[$written])) {
            $this->writeBuffer = \substr($this->writeBuffer, $written);
        } else {
            $this->loop->removeWriteStream($this->socket);
            $this->writePending = false;
            $this->writeBuffer = '';
        }
    }

    
    public function handleRead()
    {
        
        
        $chunk = @\fread($this->socket, $this->readChunk);
        if ($chunk === false || $chunk === '') {
            $this->closeError('Connection to DNS server ' . $this->nameserver . ' lost');
            return;
        }

        
        $this->readBuffer .= $chunk;

        
        while (isset($this->readBuffer[11])) {
            
            list(, $length) = \unpack('n', $this->readBuffer);
            if (!isset($this->readBuffer[$length + 1])) {
                return;
            }

            $data = \substr($this->readBuffer, 2, $length);
            $this->readBuffer = (string)substr($this->readBuffer, $length + 2);

            try {
                $response = $this->parser->parseMessage($data);
            } catch (\Exception $e) {
                
                $this->closeError('Invalid message received from DNS server ' . $this->nameserver);
                return;
            }

            
            if (!isset($this->pending[$response->id]) || $response->tc) {
                $this->closeError('Invalid response message received from DNS server ' . $this->nameserver);
                return;
            }

            $deferred = $this->pending[$response->id];
            unset($this->pending[$response->id], $this->names[$response->id]);

            $deferred->resolve($response);

            $this->checkIdle();
        }
    }

    
    public function closeError($reason, $code = 0)
    {
        $this->readBuffer = '';
        if ($this->readPending) {
            $this->loop->removeReadStream($this->socket);
            $this->readPending = false;
        }

        $this->writeBuffer = '';
        if ($this->writePending) {
            $this->loop->removeWriteStream($this->socket);
            $this->writePending = false;
        }

        if ($this->idleTimer !== null) {
            $this->loop->cancelTimer($this->idleTimer);
            $this->idleTimer = null;
        }

        @\fclose($this->socket);
        $this->socket = null;

        foreach ($this->names as $id => $name) {
            $this->pending[$id]->reject(new \RuntimeException(
                'DNS query for ' . $name . ' failed: ' . $reason,
                $code
            ));
        }
        $this->pending = $this->names = array();
    }

    
    public function checkIdle()
    {
        if ($this->idleTimer === null && !$this->names) {
            $that = $this;
            $this->idleTimer = $this->loop->addTimer($this->idlePeriod, function () use ($that) {
                $that->closeError('Idle timeout');
            });
        }
    }
}
