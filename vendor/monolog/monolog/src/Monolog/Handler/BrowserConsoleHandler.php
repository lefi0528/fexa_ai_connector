<?php declare(strict_types=1);



namespace Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Utils;
use Monolog\LogRecord;
use Monolog\Level;

use function headers_list;
use function stripos;


class BrowserConsoleHandler extends AbstractProcessingHandler
{
    protected static bool $initialized = false;

    
    protected static array $records = [];

    protected const FORMAT_HTML = 'html';
    protected const FORMAT_JS = 'js';
    protected const FORMAT_UNKNOWN = 'unknown';

    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter('[[%channel%]]{macro: autolabel} [[%level_name%]]{font-weight: bold} %message%');
    }

    
    protected function write(LogRecord $record): void
    {
        
        static::$records[] = $record;

        
        if (!static::$initialized) {
            static::$initialized = true;
            $this->registerShutdownFunction();
        }
    }

    
    public static function send(): void
    {
        $format = static::getResponseFormat();
        if ($format === self::FORMAT_UNKNOWN) {
            return;
        }

        if (\count(static::$records) > 0) {
            if ($format === self::FORMAT_HTML) {
                static::writeOutput('<script>' . self::generateScript() . '</script>');
            } else { 
                static::writeOutput(self::generateScript());
            }
            static::resetStatic();
        }
    }

    public function close(): void
    {
        self::resetStatic();
    }

    public function reset(): void
    {
        parent::reset();

        self::resetStatic();
    }

    
    public static function resetStatic(): void
    {
        static::$records = [];
    }

    
    protected function registerShutdownFunction(): void
    {
        if (PHP_SAPI !== 'cli') {
            register_shutdown_function(['Monolog\Handler\BrowserConsoleHandler', 'send']);
        }
    }

    
    protected static function writeOutput(string $str): void
    {
        echo $str;
    }

    
    protected static function getResponseFormat(): string
    {
        
        foreach (headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0) {
                return static::getResponseFormatFromContentType($header);
            }
        }

        return self::FORMAT_HTML;
    }

    
    protected static function getResponseFormatFromContentType(string $contentType): string
    {
        
        
        if (stripos($contentType, 'application/javascript') !== false || stripos($contentType, 'text/javascript') !== false) {
            return self::FORMAT_JS;
        }

        if (stripos($contentType, 'text/html') !== false) {
            return self::FORMAT_HTML;
        }

        return self::FORMAT_UNKNOWN;
    }

    private static function generateScript(): string
    {
        $script = [];
        foreach (static::$records as $record) {
            $context = self::dump('Context', $record->context);
            $extra = self::dump('Extra', $record->extra);

            if (\count($context) === 0 && \count($extra) === 0) {
                $script[] = self::call_array(self::getConsoleMethodForLevel($record->level), self::handleStyles($record->formatted));
            } else {
                $script = array_merge(
                    $script,
                    [self::call_array('groupCollapsed', self::handleStyles($record->formatted))],
                    $context,
                    $extra,
                    [self::call('groupEnd')]
                );
            }
        }

        return "(function (c) {if (c && c.groupCollapsed) {\n" . implode("\n", $script) . "\n}})(console);";
    }

    private static function getConsoleMethodForLevel(Level $level): string
    {
        return match ($level) {
            Level::Debug => 'debug',
            Level::Info, Level::Notice => 'info',
            Level::Warning => 'warn',
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => 'error',
        };
    }

    
    private static function handleStyles(string $formatted): array
    {
        $args = [];
        $format = '%c' . $formatted;
        preg_match_all('/\[\[(.*?)\]\]\{([^}]*)\}/s', $format, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach (array_reverse($matches) as $match) {
            $args[] = '"font-weight: normal"';
            $args[] = self::quote(self::handleCustomStyles($match[2][0], $match[1][0]));

            $pos = $match[0][1];
            $format = Utils::substr($format, 0, $pos) . '%c' . $match[1][0] . '%c' . Utils::substr($format, $pos + \strlen($match[0][0]));
        }

        $args[] = self::quote('font-weight: normal');
        $args[] = self::quote($format);

        return array_reverse($args);
    }

    private static function handleCustomStyles(string $style, string $string): string
    {
        static $colors = ['blue', 'green', 'red', 'magenta', 'orange', 'black', 'grey'];
        static $labels = [];

        $style = preg_replace_callback('/macro\s*:(.*?)(?:;|$)/', function (array $m) use ($string, &$colors, &$labels) {
            if (trim($m[1]) === 'autolabel') {
                
                if (!isset($labels[$string])) {
                    $labels[$string] = $colors[\count($labels) % \count($colors)];
                }
                $color = $labels[$string];

                return "background-color: $color; color: white; border-radius: 3px; padding: 0 2px 0 2px";
            }

            return $m[1];
        }, $style);

        if (null === $style) {
            $pcreErrorCode = preg_last_error();

            throw new \RuntimeException('Failed to run preg_replace_callback: ' . $pcreErrorCode . ' / ' . preg_last_error_msg());
        }

        return $style;
    }

    
    private static function dump(string $title, array $dict): array
    {
        $script = [];
        $dict = array_filter($dict, fn ($value) => $value !== null);
        if (\count($dict) === 0) {
            return $script;
        }
        $script[] = self::call('log', self::quote('%c%s'), self::quote('font-weight: bold'), self::quote($title));
        foreach ($dict as $key => $value) {
            $value = json_encode($value);
            if (false === $value) {
                $value = self::quote('');
            }
            $script[] = self::call('log', self::quote('%s: %o'), self::quote((string) $key), $value);
        }

        return $script;
    }

    private static function quote(string $arg): string
    {
        return '"' . addcslashes($arg, "\"\n\\") . '"';
    }

    
    private static function call(...$args): string
    {
        $method = array_shift($args);
        if (!\is_string($method)) {
            throw new \UnexpectedValueException('Expected the first arg to be a string, got: '.var_export($method, true));
        }

        return self::call_array($method, $args);
    }

    
    private static function call_array(string $method, array $args): string
    {
        return 'c.' . $method . '(' . implode(', ', $args) . ');';
    }
}
