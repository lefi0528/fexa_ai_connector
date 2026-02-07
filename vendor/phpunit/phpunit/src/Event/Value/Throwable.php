<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;

use const PHP_EOL;
use PHPUnit\Event\NoPreviousThrowableException;


final class Throwable
{
    
    private readonly string $className;
    private readonly string $message;
    private readonly string $description;
    private readonly string $stackTrace;
    private readonly ?Throwable $previous;

    
    public function __construct(string $className, string $message, string $description, string $stackTrace, ?self $previous)
    {
        $this->className   = $className;
        $this->message     = $message;
        $this->description = $description;
        $this->stackTrace  = $stackTrace;
        $this->previous    = $previous;
    }

    
    public function asString(): string
    {
        $buffer = $this->description();

        if (!empty($this->stackTrace())) {
            $buffer .= PHP_EOL . $this->stackTrace();
        }

        if ($this->hasPrevious()) {
            $buffer .= PHP_EOL . 'Caused by' . PHP_EOL . $this->previous()->asString();
        }

        return $buffer;
    }

    
    public function className(): string
    {
        return $this->className;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function stackTrace(): string
    {
        return $this->stackTrace;
    }

    
    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    
    public function previous(): self
    {
        if ($this->previous === null) {
            throw new NoPreviousThrowableException;
        }

        return $this->previous;
    }
}
