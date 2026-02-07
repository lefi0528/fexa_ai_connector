<?php declare(strict_types=1);



namespace Monolog\Handler;

use InvalidArgumentException;
use Monolog\Level;
use Monolog\Utils;
use Monolog\LogRecord;


class RotatingFileHandler extends StreamHandler
{
    public const FILE_PER_DAY = 'Y-m-d';
    public const FILE_PER_MONTH = 'Y-m';
    public const FILE_PER_YEAR = 'Y';

    protected string $filename;
    protected int $maxFiles;
    protected bool|null $mustRotate = null;
    protected \DateTimeImmutable $nextRotation;
    protected string $filenameFormat;
    protected string $dateFormat;

    
    public function __construct(string $filename, int $maxFiles = 0, int|string|Level $level = Level::Debug, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false, string $dateFormat = self::FILE_PER_DAY, string $filenameFormat  = '{filename}-{date}')
    {
        $this->filename = Utils::canonicalizePath($filename);
        $this->maxFiles = $maxFiles;
        $this->setFilenameFormat($filenameFormat, $dateFormat);
        $this->nextRotation = $this->getNextRotation();

        parent::__construct($this->getTimedFilename(), $level, $bubble, $filePermission, $useLocking);
    }

    
    public function close(): void
    {
        parent::close();

        if (true === $this->mustRotate) {
            $this->rotate();
        }
    }

    
    public function reset(): void
    {
        parent::reset();
    }

    
    public function setFilenameFormat(string $filenameFormat, string $dateFormat): self
    {
        $this->setDateFormat($dateFormat);
        if (substr_count($filenameFormat, '{date}') === 0) {
            throw new InvalidArgumentException(
                'Invalid filename format - format must contain at least `{date}`, because otherwise rotating is impossible.'
            );
        }
        $this->filenameFormat = $filenameFormat;
        $this->url = $this->getTimedFilename();
        $this->close();

        return $this;
    }

    
    protected function write(LogRecord $record): void
    {
        
        if (null === $this->mustRotate) {
            $this->mustRotate = null === $this->url || !file_exists($this->url);
        }

        
        if ($this->nextRotation <= $record->datetime) {
            $this->mustRotate = true;
            $this->close(); 
        }

        parent::write($record);

        if (true === $this->mustRotate) {
            $this->close(); 
        }
    }

    
    protected function rotate(): void
    {
        
        $this->url = $this->getTimedFilename();
        $this->nextRotation = $this->getNextRotation();

        $this->mustRotate = false;

        
        if (0 === $this->maxFiles) {
            return;
        }

        $logFiles = glob($this->getGlobPattern());
        if (false === $logFiles) {
            
            return;
        }

        if ($this->maxFiles >= \count($logFiles)) {
            
            return;
        }

        
        usort($logFiles, function ($a, $b) {
            return strcmp($b, $a);
        });

        foreach (\array_slice($logFiles, $this->maxFiles) as $file) {
            if (is_writable($file)) {
                
                
                set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
                    return false;
                });
                unlink($file);
                restore_error_handler();
            }
        }
    }

    protected function getTimedFilename(): string
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], date($this->dateFormat)],
            ($fileInfo['dirname'] ?? '') . '/' . $this->filenameFormat
        );

        if (isset($fileInfo['extension'])) {
            $timedFilename .= '.'.$fileInfo['extension'];
        }

        return $timedFilename;
    }

    protected function getGlobPattern(): string
    {
        $fileInfo = pathinfo($this->filename);
        $glob = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], str_replace(
                ['Y', 'y', 'm', 'd'],
                ['[0-9][0-9][0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]'],
                $this->dateFormat
            )],
            ($fileInfo['dirname'] ?? '') . '/' . $this->filenameFormat
        );
        if (isset($fileInfo['extension'])) {
            $glob .= '.'.$fileInfo['extension'];
        }

        return $glob;
    }

    protected function setDateFormat(string $dateFormat): void
    {
        if (0 === preg_match('{^[Yy](([/_.-]?m)([/_.-]?d)?)?$}', $dateFormat)) {
            throw new InvalidArgumentException(
                'Invalid date format - format must be one of '.
                'RotatingFileHandler::FILE_PER_DAY ("Y-m-d"), RotatingFileHandler::FILE_PER_MONTH ("Y-m") '.
                'or RotatingFileHandler::FILE_PER_YEAR ("Y"), or you can set one of the '.
                'date formats using slashes, underscores and/or dots instead of dashes.'
            );
        }
        $this->dateFormat = $dateFormat;
    }

    protected function getNextRotation(): \DateTimeImmutable
    {
        return match (str_replace(['/','_','.'], '-', $this->dateFormat)) {
            self::FILE_PER_MONTH => (new \DateTimeImmutable('first day of next month'))->setTime(0, 0, 0),
            self::FILE_PER_YEAR => (new \DateTimeImmutable('first day of January next year'))->setTime(0, 0, 0),
            default => (new \DateTimeImmutable('tomorrow'))->setTime(0, 0, 0),
        };
    }
}
