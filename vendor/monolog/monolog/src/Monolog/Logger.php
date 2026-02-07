<?php declare(strict_types=1);



namespace Monolog;

use Closure;
use DateTimeZone;
use Fiber;
use Monolog\Handler\HandlerInterface;
use Monolog\Processor\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Throwable;
use Stringable;
use WeakMap;


class Logger implements LoggerInterface, ResettableInterface
{
    
    public const DEBUG = 100;

    
    public const INFO = 200;

    
    public const NOTICE = 250;

    
    public const WARNING = 300;

    
    public const ERROR = 400;

    
    public const CRITICAL = 500;

    
    public const ALERT = 550;

    
    public const EMERGENCY = 600;

    
    public const API = 3;

    
    private const RFC_5424_LEVELS = [
        7 => Level::Debug,
        6 => Level::Info,
        5 => Level::Notice,
        4 => Level::Warning,
        3 => Level::Error,
        2 => Level::Critical,
        1 => Level::Alert,
        0 => Level::Emergency,
    ];

    protected string $name;

    
    protected array $handlers;

    
    protected array $processors;

    protected bool $microsecondTimestamps = true;

    protected DateTimeZone $timezone;

    protected Closure|null $exceptionHandler = null;

    
    private int $logDepth = 0;

    
    private WeakMap $fiberLogDepth;

    
    private bool $detectCycles = true;

    
    public function __construct(string $name, array $handlers = [], array $processors = [], DateTimeZone|null $timezone = null)
    {
        $this->name = $name;
        $this->setHandlers($handlers);
        $this->processors = $processors;
        $this->timezone = $timezone ?? new DateTimeZone(date_default_timezone_get());
        $this->fiberLogDepth = new \WeakMap();
    }

    public function getName(): string
    {
        return $this->name;
    }

    
    public function withName(string $name): self
    {
        $new = clone $this;
        $new->name = $name;

        return $new;
    }

    
    public function pushHandler(HandlerInterface $handler): self
    {
        array_unshift($this->handlers, $handler);

        return $this;
    }

    
    public function popHandler(): HandlerInterface
    {
        if (0 === \count($this->handlers)) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }

        return array_shift($this->handlers);
    }

    
    public function setHandlers(array $handlers): self
    {
        $this->handlers = [];
        foreach (array_reverse($handlers) as $handler) {
            $this->pushHandler($handler);
        }

        return $this;
    }

    
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    
    public function pushProcessor(ProcessorInterface|callable $callback): self
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    
    public function popProcessor(): callable
    {
        if (0 === \count($this->processors)) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    
    public function getProcessors(): array
    {
        return $this->processors;
    }

    
    public function useMicrosecondTimestamps(bool $micro): self
    {
        $this->microsecondTimestamps = $micro;

        return $this;
    }

    
    public function useLoggingLoopDetection(bool $detectCycles): self
    {
        $this->detectCycles = $detectCycles;

        return $this;
    }

    
    public function addRecord(int|Level $level, string $message, array $context = [], JsonSerializableDateTimeImmutable|null $datetime = null): bool
    {
        if (\is_int($level) && isset(self::RFC_5424_LEVELS[$level])) {
            $level = self::RFC_5424_LEVELS[$level];
        }

        if ($this->detectCycles) {
            if (null !== ($fiber = Fiber::getCurrent())) {
                $logDepth = $this->fiberLogDepth[$fiber] = ($this->fiberLogDepth[$fiber] ?? 0) + 1;
            } else {
                $logDepth = ++$this->logDepth;
            }
        } else {
            $logDepth = 0;
        }

        if ($logDepth === 3) {
            $this->warning('A possible infinite logging loop was detected and aborted. It appears some of your handler code is triggering logging, see the previous log record for a hint as to what may be the cause.');

            return false;
        } elseif ($logDepth >= 5) { 
            return false;
        }

        try {
            $recordInitialized = \count($this->processors) === 0;

            $record = new LogRecord(
                datetime: $datetime ?? new JsonSerializableDateTimeImmutable($this->microsecondTimestamps, $this->timezone),
                channel: $this->name,
                level: self::toMonologLevel($level),
                message: $message,
                context: $context,
                extra: [],
            );
            $handled = false;

            foreach ($this->handlers as $handler) {
                if (false === $recordInitialized) {
                    
                    if (!$handler->isHandling($record)) {
                        continue;
                    }

                    try {
                        foreach ($this->processors as $processor) {
                            $record = $processor($record);
                        }
                        $recordInitialized = true;
                    } catch (Throwable $e) {
                        $this->handleException($e, $record);

                        return true;
                    }
                }

                
                try {
                    $handled = true;
                    if (true === $handler->handle(clone $record)) {
                        break;
                    }
                } catch (Throwable $e) {
                    $this->handleException($e, $record);

                    return true;
                }
            }

            return $handled;
        } finally {
            if ($this->detectCycles) {
                if (isset($fiber)) {
                    $this->fiberLogDepth[$fiber]--;
                } else {
                    $this->logDepth--;
                }
            }
        }
    }

    
    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
    }

    
    public function reset(): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof ResettableInterface) {
                $handler->reset();
            }
        }

        foreach ($this->processors as $processor) {
            if ($processor instanceof ResettableInterface) {
                $processor->reset();
            }
        }
    }

    
    public static function getLevelName(int|Level $level): string
    {
        return self::toMonologLevel($level)->getName();
    }

    
    public static function toMonologLevel(string|int|Level $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }

        if (\is_string($level)) {
            if (is_numeric($level)) {
                $levelEnum = Level::tryFrom((int) $level);
                if ($levelEnum === null) {
                    throw new InvalidArgumentException('Level "'.$level.'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
                }

                return $levelEnum;
            }

            
            
            $upper = strtr(substr($level, 0, 1), 'dinweca', 'DINWECA') . strtolower(substr($level, 1));
            if (\defined(Level::class.'::'.$upper)) {
                return \constant(Level::class . '::' . $upper);
            }

            throw new InvalidArgumentException('Level "'.$level.'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
        }

        $levelEnum = Level::tryFrom($level);
        if ($levelEnum === null) {
            throw new InvalidArgumentException('Level "'.var_export($level, true).'" is not defined, use one of: '.implode(', ', Level::NAMES + Level::VALUES));
        }

        return $levelEnum;
    }

    
    public function isHandling(int|string|Level $level): bool
    {
        $record = new LogRecord(
            datetime: new JsonSerializableDateTimeImmutable($this->microsecondTimestamps, $this->timezone),
            channel: $this->name,
            message: '',
            level: self::toMonologLevel($level),
        );

        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record)) {
                return true;
            }
        }

        return false;
    }

    
    public function setExceptionHandler(Closure|null $callback): self
    {
        $this->exceptionHandler = $callback;

        return $this;
    }

    public function getExceptionHandler(): Closure|null
    {
        return $this->exceptionHandler;
    }

    
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (!$level instanceof Level) {
            if (!\is_string($level) && !\is_int($level)) {
                throw new \InvalidArgumentException('$level is expected to be a string, int or '.Level::class.' instance');
            }

            if (isset(self::RFC_5424_LEVELS[$level])) {
                $level = self::RFC_5424_LEVELS[$level];
            }

            $level = static::toMonologLevel($level);
        }

        $this->addRecord($level, (string) $message, $context);
    }

    
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Debug, (string) $message, $context);
    }

    
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Info, (string) $message, $context);
    }

    
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Notice, (string) $message, $context);
    }

    
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Warning, (string) $message, $context);
    }

    
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Error, (string) $message, $context);
    }

    
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Critical, (string) $message, $context);
    }

    
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Alert, (string) $message, $context);
    }

    
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->addRecord(Level::Emergency, (string) $message, $context);
    }

    
    public function setTimezone(DateTimeZone $tz): self
    {
        $this->timezone = $tz;

        return $this;
    }

    
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    
    protected function handleException(Throwable $e, LogRecord $record): void
    {
        if (null === $this->exceptionHandler) {
            throw $e;
        }

        ($this->exceptionHandler)($e, $record);
    }

    
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'handlers' => $this->handlers,
            'processors' => $this->processors,
            'microsecondTimestamps' => $this->microsecondTimestamps,
            'timezone' => $this->timezone,
            'exceptionHandler' => $this->exceptionHandler,
            'logDepth' => $this->logDepth,
            'detectCycles' => $this->detectCycles,
        ];
    }

    
    public function __unserialize(array $data): void
    {
        foreach (['name', 'handlers', 'processors', 'microsecondTimestamps', 'timezone', 'exceptionHandler', 'logDepth', 'detectCycles'] as $property) {
            if (isset($data[$property])) {
                $this->$property = $data[$property];
            }
        }

        $this->fiberLogDepth = new \WeakMap();
    }
}
