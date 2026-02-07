<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use PHPUnit\Util\Xml\XmlException;


abstract class SchemaDetectionResult
{
    
    public function detected(): bool
    {
        return false;
    }

    
    public function version(): string
    {
        throw new XmlException('No supported schema was detected');
    }
}
