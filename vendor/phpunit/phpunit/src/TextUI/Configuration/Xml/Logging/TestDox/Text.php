<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration\Logging\TestDox;

use PHPUnit\TextUI\Configuration\File;


final class Text
{
    private readonly File $target;

    public function __construct(File $target)
    {
        $this->target = $target;
    }

    public function target(): File
    {
        return $this->target;
    }
}
