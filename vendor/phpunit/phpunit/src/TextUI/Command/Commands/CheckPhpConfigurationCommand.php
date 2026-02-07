<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Command;

use const E_ALL;
use const PHP_EOL;
use function extension_loaded;
use function in_array;
use function ini_get;
use function max;
use function sprintf;
use function strlen;
use PHPUnit\Runner\Version;
use PHPUnit\Util\Color;
use SebastianBergmann\Environment\Console;


final class CheckPhpConfigurationCommand implements Command
{
    private readonly bool $colorize;

    public function __construct()
    {
        $this->colorize = (new Console)->hasColorSupport();
    }

    public function execute(): Result
    {
        $lines         = [];
        $shellExitCode = 0;

        foreach ($this->settings() as $name => $setting) {
            foreach ($setting['requiredExtensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    
                    continue 2;
                    
                }
            }

            $actualValue = ini_get($name);

            if (in_array($actualValue, $setting['expectedValues'], true)) {
                $check = $this->ok();
            } else {
                $check         = $this->notOk($actualValue);
                $shellExitCode = 1;
            }

            $lines[] = [
                sprintf(
                    '%s = %s',
                    $name,
                    $setting['valueForConfiguration'],
                ),
                $check,
            ];
        }

        $maxLength = 0;

        foreach ($lines as $line) {
            $maxLength = max($maxLength, strlen($line[0]));
        }

        $buffer = sprintf(
            'Checking whether PHP is configured according to https://docs.phpunit.de/en/%s/installation.html#configuring-php-for-development' . PHP_EOL . PHP_EOL,
            Version::series(),
        );

        foreach ($lines as $line) {
            $buffer .= sprintf(
                '%-' . $maxLength . 's ... %s' . PHP_EOL,
                $line[0],
                $line[1],
            );
        }

        return Result::from($buffer, $shellExitCode);
    }

    
    private function ok(): string
    {
        if (!$this->colorize) {
            return 'ok';
        }

        
        return Color::colorizeTextBox('fg-green, bold', 'ok');
        
    }

    
    private function notOk(string $actualValue): string
    {
        $message = sprintf('not ok (%s)', $actualValue);

        if (!$this->colorize) {
            return $message;
        }

        
        return Color::colorizeTextBox('fg-red, bold', $message);
        
    }

    
    private function settings(): array
    {
        return [
            'display_errors' => [
                'expectedValues'        => ['1'],
                'valueForConfiguration' => 'On',
                'requiredExtensions'    => [],
            ],
            'display_startup_errors' => [
                'expectedValues'        => ['1'],
                'valueForConfiguration' => 'On',
                'requiredExtensions'    => [],
            ],
            'error_reporting' => [
                'expectedValues'        => ['-1', (string) E_ALL],
                'valueForConfiguration' => '-1',
                'requiredExtensions'    => [],
            ],
            'xdebug.show_exception_trace' => [
                'expectedValues'        => ['0'],
                'valueForConfiguration' => '0',
                'requiredExtensions'    => ['xdebug'],
            ],
            'zend.assertions' => [
                'expectedValues'        => ['1'],
                'valueForConfiguration' => '1',
                'requiredExtensions'    => [],
            ],
            'assert.exception' => [
                'expectedValues'        => ['1'],
                'valueForConfiguration' => '1',
                'requiredExtensions'    => [],
            ],
            'memory_limit' => [
                'expectedValues'        => ['-1'],
                'valueForConfiguration' => '-1',
                'requiredExtensions'    => [],
            ],
        ];
    }
}
