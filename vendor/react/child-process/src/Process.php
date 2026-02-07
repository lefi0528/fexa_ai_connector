<?php

namespace React\ChildProcess;

use Evenement\EventEmitter;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ReadableResourceStream;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;
use React\Stream\DuplexResourceStream;
use React\Stream\DuplexStreamInterface;


class Process extends EventEmitter
{
    
    public $stdin;

    
    public $stdout;

    
    public $stderr;

    
    public $pipes = array();

    private $cmd;
    private $cwd;
    private $env;
    private $fds;

    private $enhanceSigchildCompatibility;
    private $sigchildPipe;

    private $process;
    private $status;
    private $exitCode;
    private $fallbackExitCode;
    private $stopSignal;
    private $termSignal;

    private static $sigchild;

    
    public function __construct($cmd, $cwd = null, $env = null, $fds = null)
    {
        if ($env !== null && !\is_array($env)) { 
            throw new \InvalidArgumentException('Argument #3 ($env) expected null|array');
        }
        if ($fds !== null && !\is_array($fds)) { 
            throw new \InvalidArgumentException('Argument #4 ($fds) expected null|array');
        }
        if (!\function_exists('proc_open')) {
            throw new \LogicException('The Process class relies on proc_open(), which is not available on your PHP installation.');
        }

        $this->cmd = $cmd;
        $this->cwd = $cwd;

        if (null !== $env) {
            $this->env = array();
            foreach ($env as $key => $value) {
                $this->env[(binary) $key] = (binary) $value;
            }
        }

        if ($fds === null) {
            $fds = array(
                array('pipe', 'r'), 
                array('pipe', 'w'), 
                array('pipe', 'w'), 
            );
        }

        if (\DIRECTORY_SEPARATOR === '\\') {
            foreach ($fds as $fd) {
                if (isset($fd[0]) && $fd[0] === 'pipe') {
                    throw new \LogicException('Process pipes are not supported on Windows due to their blocking nature on Windows');
                }
            }
        }

        $this->fds = $fds;
        $this->enhanceSigchildCompatibility = self::isSigchildEnabled();
    }

    
    public function start($loop = null, $interval = 0.1)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) { 
            throw new \InvalidArgumentException('Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        }
        if ($this->isRunning()) {
            throw new \RuntimeException('Process is already running');
        }

        $loop = $loop ?: Loop::get();
        $cmd = $this->cmd;
        $fdSpec = $this->fds;
        $sigchild = null;

        
        if ($this->enhanceSigchildCompatibility) {
            $fdSpec[] = array('pipe', 'w');
            \end($fdSpec);
            $sigchild = \key($fdSpec);

            
            if ($sigchild < 3) {
                $fdSpec[3] = $fdSpec[$sigchild];
                unset($fdSpec[$sigchild]);
                $sigchild = 3;
            }

            $cmd = \sprintf('(%s) ' . $sigchild . '>/dev/null; code=$?; echo $code >&' . $sigchild . '; exit $code', $cmd);
        }

        
        
        $options = array();
        if (\DIRECTORY_SEPARATOR === '\\') {
            $options['bypass_shell'] = true;
            $options['suppress_errors'] = true;
        }

        $errstr = '';
        \set_error_handler(function ($_, $error) use (&$errstr) {
            
            
            $errstr = $error;
        });

        $pipes = array();
        $this->process = @\proc_open($cmd, $fdSpec, $pipes, $this->cwd, $this->env, $options);

        \restore_error_handler();

        if (!\is_resource($this->process)) {
            throw new \RuntimeException('Unable to launch a new process: ' . $errstr);
        }

        
        $that = $this;
        $closeCount = 0;
        $streamCloseHandler = function () use (&$closeCount, $loop, $interval, $that) {
            $closeCount--;

            if ($closeCount > 0) {
                return;
            }

            
            if (!$that->isRunning()) {
                $that->close();
                $that->emit('exit', array($that->getExitCode(), $that->getTermSignal()));
                return;
            }

            
            $loop->addPeriodicTimer($interval, function ($timer) use ($that, $loop) {
                if (!$that->isRunning()) {
                    $loop->cancelTimer($timer);
                    $that->close();
                    $that->emit('exit', array($that->getExitCode(), $that->getTermSignal()));
                }
            });
        };

        if ($sigchild !== null) {
            $this->sigchildPipe = $pipes[$sigchild];
            unset($pipes[$sigchild]);
        }

        foreach ($pipes as $n => $fd) {
            
            $meta = \stream_get_meta_data($fd);
            $mode = $meta['mode'] === '' ? ($this->fds[$n][1] === 'r' ? 'w' : 'r') : $meta['mode'];

            if ($mode === 'r+') {
                $stream = new DuplexResourceStream($fd, $loop);
                $stream->on('close', $streamCloseHandler);
                $closeCount++;
            } elseif ($mode === 'w') {
                $stream = new WritableResourceStream($fd, $loop);
            } else {
                $stream = new ReadableResourceStream($fd, $loop);
                $stream->on('close', $streamCloseHandler);
                $closeCount++;
            }
            $this->pipes[$n] = $stream;
        }

        $this->stdin  = isset($this->pipes[0]) ? $this->pipes[0] : null;
        $this->stdout = isset($this->pipes[1]) ? $this->pipes[1] : null;
        $this->stderr = isset($this->pipes[2]) ? $this->pipes[2] : null;

        
        if (!$closeCount) {
            $streamCloseHandler();
        }
    }

    
    public function close()
    {
        if ($this->process === null) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            $pipe->close();
        }

        if ($this->enhanceSigchildCompatibility) {
            $this->pollExitCodePipe();
            $this->closeExitCodePipe();
        }

        $exitCode = \proc_close($this->process);
        $this->process = null;

        if ($this->exitCode === null && $exitCode !== -1) {
            $this->exitCode = $exitCode;
        }

        if ($this->exitCode === null && $this->status['exitcode'] !== -1) {
            $this->exitCode = $this->status['exitcode'];
        }

        if ($this->exitCode === null && $this->fallbackExitCode !== null) {
            $this->exitCode = $this->fallbackExitCode;
            $this->fallbackExitCode = null;
        }
    }

    
    public function terminate($signal = null)
    {
        if ($this->process === null) {
            return false;
        }

        if ($signal !== null) {
            return \proc_terminate($this->process, $signal);
        }

        return \proc_terminate($this->process);
    }

    
    public function getCommand()
    {
        return $this->cmd;
    }

    
    public function getExitCode()
    {
        return $this->exitCode;
    }

    
    public function getPid()
    {
        $status = $this->getCachedStatus();

        return $status !== null ? $status['pid'] : null;
    }

    
    public function getStopSignal()
    {
        return $this->stopSignal;
    }

    
    public function getTermSignal()
    {
        return $this->termSignal;
    }

    
    public function isRunning()
    {
        if ($this->process === null) {
            return false;
        }

        $status = $this->getFreshStatus();

        return $status !== null ? $status['running'] : false;
    }

    
    public function isStopped()
    {
        $status = $this->getFreshStatus();

        return $status !== null ? $status['stopped'] : false;
    }

    
    public function isTerminated()
    {
        $status = $this->getFreshStatus();

        return $status !== null ? $status['signaled'] : false;
    }

    
    public final static function isSigchildEnabled()
    {
        if (null !== self::$sigchild) {
            return self::$sigchild;
        }

        if (!\function_exists('phpinfo')) {
            return self::$sigchild = false; 
        }

        \ob_start();
        \phpinfo(INFO_GENERAL);

        return self::$sigchild = false !== \strpos(\ob_get_clean(), '--enable-sigchild');
    }

    
    public final static function setSigchildEnabled($sigchild)
    {
        self::$sigchild = (bool) $sigchild;
    }

    
    private function pollExitCodePipe()
    {
        if ($this->sigchildPipe === null) {
            return;
        }

        $r = array($this->sigchildPipe);
        $w = $e = null;

        $n = @\stream_select($r, $w, $e, 0);

        if (1 !== $n) {
            return;
        }

        $data = \fread($r[0], 8192);

        if (\strlen($data) > 0) {
            $this->fallbackExitCode = (int) $data;
        }
    }

    
    private function closeExitCodePipe()
    {
        if ($this->sigchildPipe === null) {
            return;
        }

        \fclose($this->sigchildPipe);
        $this->sigchildPipe = null;
    }

    
    private function getCachedStatus()
    {
        if ($this->status === null) {
            $this->updateStatus();
        }

        return $this->status;
    }

    
    private function getFreshStatus()
    {
        $this->updateStatus();

        return $this->status;
    }

    
    private function updateStatus()
    {
        if ($this->process === null) {
            return;
        }

        $this->status = \proc_get_status($this->process);

        if ($this->status === false) {
            throw new \UnexpectedValueException('proc_get_status() failed');
        }

        if ($this->status['stopped']) {
            $this->stopSignal = $this->status['stopsig'];
        }

        if ($this->status['signaled']) {
            $this->termSignal = $this->status['termsig'];
        }

        if (!$this->status['running'] && -1 !== $this->status['exitcode']) {
            $this->exitCode = $this->status['exitcode'];
        }
    }
}
