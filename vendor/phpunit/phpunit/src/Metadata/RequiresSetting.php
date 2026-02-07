<?php declare(strict_types=1);

namespace PHPUnit\Metadata;


final class RequiresSetting extends Metadata
{
    
    private readonly string $setting;

    
    private readonly string $value;

    
    protected function __construct(int $level, string $setting, string $value)
    {
        parent::__construct($level);

        $this->setting = $setting;
        $this->value   = $value;
    }

    
    public function isRequiresSetting(): bool
    {
        return true;
    }

    
    public function setting(): string
    {
        return $this->setting;
    }

    
    public function value(): string
    {
        return $this->value;
    }
}
