<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration\CodeCoverage\Report;

use PHPUnit\TextUI\Configuration\Directory;


final class Xml
{
    private readonly Directory $target;

    public function __construct(Directory $target)
    {
        $this->target = $target;
    }

    public function target(): Directory
    {
        return $this->target;
    }
}
