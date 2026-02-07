<?php

namespace Mcp\StdioCalculatorExample;

use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpTool;

class McpElements
{
    private array $config = [
        'precision' => 2,
        'allow_negative' => true,
    ];

    
    #[McpTool(name: 'calculate')]
    public function calculate(float $a, float $b, string $operation): float|string
    {
        
        fwrite(STDERR, "Calculate tool called: a=$a, b=$b, op=$operation\n");

        $op = strtolower($operation);
        $result = null;

        switch ($op) {
            case 'add':
                $result = $a + $b;
                break;
            case 'subtract':
                $result = $a - $b;
                break;
            case 'multiply':
                $result = $a * $b;
                break;
            case 'divide':
                if ($b == 0) {
                    return 'Error: Division by zero.';
                }
                $result = $a / $b;
                break;
            default:
                return "Error: Unknown operation '{$operation}'. Supported: add, subtract, multiply, divide.";
        }

        if (! $this->config['allow_negative'] && $result < 0) {
            return 'Error: Negative results are disabled.';
        }

        return round($result, $this->config['precision']);
    }

    
    #[McpResource(
        uri: 'config://calculator/settings',
        name: 'calculator_config',
        description: 'Current settings for the calculator tool (precision, allow_negative).',
        mimeType: 'application/json' 
    )]
    public function getConfiguration(): array
    {
        fwrite(STDERR, "Resource config://calculator/settings read.\n");

        return $this->config;
    }

    
    #[McpTool(name: 'update_setting')]
    public function updateSetting(string $setting, mixed $value): array
    {
        fwrite(STDERR, "Update Setting tool called: setting=$setting, value=".var_export($value, true)."\n");
        if (! array_key_exists($setting, $this->config)) {
            return ['success' => false, 'error' => "Unknown setting '{$setting}'."];
        }

        if ($setting === 'precision') {
            if (! is_int($value) || $value < 0 || $value > 10) {
                return ['success' => false, 'error' => 'Invalid precision value. Must be integer between 0 and 10.'];
            }
            $this->config['precision'] = $value;

            
            
            return ['success' => true, 'message' => "Precision updated to {$value}."];
        }

        if ($setting === 'allow_negative') {
            if (! is_bool($value)) {
                
                if (in_array(strtolower((string) $value), ['true', '1', 'yes', 'on'])) {
                    $value = true;
                } elseif (in_array(strtolower((string) $value), ['false', '0', 'no', 'off'])) {
                    $value = false;
                } else {
                    return ['success' => false, 'error' => 'Invalid allow_negative value. Must be boolean (true/false).'];
                }
            }
            $this->config['allow_negative'] = $value;

            
            return ['success' => true, 'message' => 'Allow negative results set to '.($value ? 'true' : 'false').'.'];
        }

        return ['success' => false, 'error' => 'Internal error handling setting.']; 
    }
}
