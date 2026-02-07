<?php declare(strict_types=1);

namespace PHPUnit\Framework\TestStatus;


abstract class TestStatus
{
    private readonly string $message;

    public static function from(int $status): self
    {
        return match ($status) {
            0       => self::success(),
            1       => self::skipped(),
            2       => self::incomplete(),
            3       => self::notice(),
            4       => self::deprecation(),
            5       => self::risky(),
            6       => self::warning(),
            7       => self::failure(),
            8       => self::error(),
            default => self::unknown(),
        };
    }

    public static function unknown(): self
    {
        return new Unknown;
    }

    public static function success(): self
    {
        return new Success;
    }

    public static function skipped(string $message = ''): self
    {
        return new Skipped($message);
    }

    public static function incomplete(string $message = ''): self
    {
        return new Incomplete($message);
    }

    public static function notice(string $message = ''): self
    {
        return new Notice($message);
    }

    public static function deprecation(string $message = ''): self
    {
        return new Deprecation($message);
    }

    public static function failure(string $message = ''): self
    {
        return new Failure($message);
    }

    public static function error(string $message = ''): self
    {
        return new Error($message);
    }

    public static function warning(string $message = ''): self
    {
        return new Warning($message);
    }

    public static function risky(string $message = ''): self
    {
        return new Risky($message);
    }

    private function __construct(string $message = '')
    {
        $this->message = $message;
    }

    
    public function isKnown(): bool
    {
        return false;
    }

    
    public function isUnknown(): bool
    {
        return false;
    }

    
    public function isSuccess(): bool
    {
        return false;
    }

    
    public function isSkipped(): bool
    {
        return false;
    }

    
    public function isIncomplete(): bool
    {
        return false;
    }

    
    public function isNotice(): bool
    {
        return false;
    }

    
    public function isDeprecation(): bool
    {
        return false;
    }

    
    public function isFailure(): bool
    {
        return false;
    }

    
    public function isError(): bool
    {
        return false;
    }

    
    public function isWarning(): bool
    {
        return false;
    }

    
    public function isRisky(): bool
    {
        return false;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function isMoreImportantThan(self $other): bool
    {
        return $this->asInt() > $other->asInt();
    }

    abstract public function asInt(): int;

    abstract public function asString(): string;
}
