<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\ResettableInterface;
use Monolog\LogRecord;


class UidProcessor implements ProcessorInterface, ResettableInterface
{
    
    private string $uid;

    
    public function __construct(int $length = 7)
    {
        if ($length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }

        $this->uid = $this->generateUid($length);
    }

    
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['uid'] = $this->uid;

        return $record;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function reset(): void
    {
        $this->uid = $this->generateUid(\strlen($this->uid));
    }

    
    private function generateUid(int $length): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}
