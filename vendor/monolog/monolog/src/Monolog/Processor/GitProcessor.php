<?php declare(strict_types=1);



namespace Monolog\Processor;

use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Monolog\LogRecord;


class GitProcessor implements ProcessorInterface
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

        $record->extra['git'] = self::getGitInfo();

        return $record;
    }

    
    private static function getGitInfo(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $branches = shell_exec('git branch -v --no-abbrev');
        if (\is_string($branches) && 1 === preg_match('{^\* (.+?)\s+([a-f0-9]{40})(?:\s|$)}m', $branches, $matches)) {
            return self::$cache = [
                'branch' => $matches[1],
                'commit' => $matches[2],
            ];
        }

        return self::$cache = [];
    }
}
