<?php declare(strict_types=1);

namespace PHPUnit\TextUI\Configuration;

use function count;
use function iterator_count;
use Countable;
use Iterator;


final class IniSettingCollectionIterator implements Countable, Iterator
{
    
    private readonly array $iniSettings;
    private int $position = 0;

    public function __construct(IniSettingCollection $iniSettings)
    {
        $this->iniSettings = $iniSettings->asArray();
    }

    public function count(): int
    {
        return iterator_count($this);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < count($this->iniSettings);
    }

    public function key(): int
    {
        return $this->position;
    }

    public function current(): IniSetting
    {
        return $this->iniSettings[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
