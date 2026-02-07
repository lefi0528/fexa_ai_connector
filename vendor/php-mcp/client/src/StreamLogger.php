<?php

namespace PhpMcp\Client;

use InvalidArgumentException;
use LogicException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;


class StreamLogger extends AbstractLogger
{
    protected const LOG_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    protected const DATE_FORMAT = 'Y-m-d H:i:s.u'; 

    
    protected $stream = null;

    
    protected ?string $url = null;

    
    protected ?int $filePermission; 

    
    protected bool $useLocking;

    
    protected string $fileOpenMode;

    
    private ?bool $dirCreated = null;

    
    private ?string $errorMessage = null;

    
    private bool $retrying = false;

    
    protected static array $levels = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    
    protected int $minimumLevelSeverity;

    
    protected string $channel;

    
    public function __construct(
        mixed $stream = STDOUT,
        string $minimumLevel = LogLevel::DEBUG,
        string $channel = 'mcp',
        ?int $filePermission = 0644,
        bool $useLocking = false,
        string $fileOpenMode = 'a'
    ) {
        if (! isset(static::$levels[$minimumLevel])) {
            throw new InvalidArgumentException("Invalid minimum log level specified: {$minimumLevel}");
        }
        $this->minimumLevelSeverity = static::$levels[$minimumLevel];
        $this->channel = $channel;

        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = self::canonicalizePath($stream);
        } else {
            throw new InvalidArgumentException('A stream must be a resource or a string path.');
        }

        $this->fileOpenMode = $fileOpenMode;
        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    
    public function log($level, Stringable|string $message, array $context = []): void
    {
        if (! isset(static::$levels[$level])) {
            
            
            
            
            $logLevelSeverity = static::$levels[LogLevel::DEBUG]; 
            
        } else {
            $logLevelSeverity = static::$levels[$level];
        }

        
        if ($logLevelSeverity < $this->minimumLevelSeverity) {
            return;
        }

        
        if (! is_resource($this->stream)) {
            if ($this->url === null || $this->url === '') {
                throw new LogicException('Missing stream url, the stream cannot be opened. This may be caused by a premature call to close().');
            }
            $this->createDir($this->url);
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            try {
                $stream = fopen($this->url, $this->fileOpenMode);
                if ($this->filePermission !== null) {
                    @chmod($this->url, $this->filePermission);
                }
            } finally {
                restore_error_handler();
            }

            if (! is_resource($stream)) {
                $this->stream = null;
                throw new RuntimeException(sprintf('The stream "%s" could not be opened: %s', $this->url, $this->errorMessage ?? 'Unknown error'));
            }
            $this->stream = $stream;
        }

        
        
        $message = (string) $message;
        
        $interpolatedMessage = $this->interpolate($message, $context);
        
        $record = [
            'message' => $interpolatedMessage,
            'context' => $context,
            'level' => $logLevelSeverity,
            'level_name' => strtoupper($level),
            'channel' => $this->channel,
            'datetime' => microtime(true), 
            'extra' => [],
        ];
        
        $formattedMessage = $this->formatRecord($record);

        
        $stream = $this->stream;
        if ($this->useLocking) {
            flock($stream, LOCK_EX);
        }

        $this->errorMessage = null;
        set_error_handler([$this, 'customErrorHandler']);
        try {
            fwrite($stream, $formattedMessage);
        } finally {
            restore_error_handler();
        }

        if ($this->errorMessage !== null) {
            $error = $this->errorMessage;
            
            if (! $this->retrying && $this->url !== null && $this->url !== 'php://memory') {
                $this->retrying = true;
                $this->close();
                $this->log($level, $message, $context); 
                $this->retrying = false; 

                return;
            }
            
            throw new RuntimeException(sprintf('Could not write to stream "%s": %s', $this->url ?? 'Resource', $error));
        }

        $this->retrying = false;
        if ($this->useLocking) {
            flock($stream, LOCK_UN);
        }
    }

    
    public function close(): void
    {
        if ($this->url !== null && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->dirCreated = null; 
    }

    
    protected function interpolate(string $message, array $context): string
    {
        if (! str_contains($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if ($val === null || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{'.$key.'}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{'.$key.'}'] = '[object '.get_class($val).']';
            } else {
                $replacements['{'.$key.'}'] = '['.gettype($val).']';
            }
        }

        return strtr($message, $replacements);
    }

    
    protected function formatRecord(array $record): string
    {
        $vars = $record;
        $vars['datetime'] = date(static::DATE_FORMAT, (int) $record['datetime']);
        
        $vars['context'] = ! empty($record['context']) ? json_encode($record['context']) : '';
        
        $vars['extra'] = ! empty($record['extra']) ? json_encode($record['extra']) : '';

        $output = static::LOG_FORMAT;
        foreach ($vars as $var => $val) {
            if (str_contains($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', (string) $val, $output);
            }
        }

        
        $output = preg_replace('/%(?:[a-zA-Z0-9_]+)%/', '', $output) ?? $output;

        return $output;
    }

    

    
    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir|fwrite|chmod)\(.*?\): }U', '', $msg); 

        return true;
    }

    
    private function createDir(string $url): void
    {
        if ($this->dirCreated === true) {
            return;
        }

        $dir = $this->getDirFromStream($url);
        if ($dir !== null && ! is_dir($dir)) {
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            $status = mkdir($dir, 0777, true); 
            restore_error_handler();

            if ($status === false && ! is_dir($dir)) { 
                throw new RuntimeException(sprintf('Could not create directory "%s": %s', $dir, $this->errorMessage ?? 'Unknown error'));
            }
        }
        $this->dirCreated = true;
    }

    
    private function getDirFromStream(string $stream): ?string
    {
        if (str_starts_with($stream, 'file://')) {
            return dirname(substr($stream, 7));
        }
        
        if (! str_contains($stream, '://')) {
            return dirname($stream);
        }

        
        return null;
    }

    
    public static function canonicalizePath(string $path): string
    {
        $prefix = '';
        if (str_starts_with($path, 'file://')) {
            $path = substr($path, 7);
            $prefix = 'file://';
        }

        
        if (str_contains($path, '://') || str_starts_with($path, '/') || preg_match('{^[a-zA-Z]:[/\\]}', $path) || str_starts_with($path, '\\')) {
            return $prefix.$path;
        }

        
        $absolutePath = getcwd();
        if ($absolutePath === false) {
            throw new RuntimeException('Could not determine current working directory.');
        }
        $path = $absolutePath.'/'.$path;

        
        
        $path = preg_replace('{[/\\]\.\.?([/\\]|$)}', '/', $path);

        return $prefix.$path;
    }

    
    public function __destruct()
    {
        $this->close();
    }
}
