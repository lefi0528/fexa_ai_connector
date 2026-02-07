<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class MercurialProcessor implements ProcessorInterface
{
    private Level $level;
    
    private static $cache = null;

    
    public function __construct(int|string|Level $level = Level::Debug)
    {
        $this->level = Logger::toMonologLevel($level);
    }

    
    public function __invoke(LogRecord $record): LogRecord
    {
        
        if ($record->level->isLowerThan($this->level)) {
            return $record;
        }

        $record->extra['hg'] = self::getMercurialInfo();

        return $record;
    }

    
    private static function getMercurialInfo(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $result = explode(' ', trim((string) shell_exec('hg id -nb')));
        if (\count($result) >= 3) {
            return self::$cache = [
                'branch' => $result[1],
                'revision' => $result[2],
            ];
        }
        if (\count($result) === 2) {
            return self::$cache = [
                'branch' => $result[1],
                'revision' => $result[0],
            ];
        }

        return self::$cache = [];
    }
}
