<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use function assert;
use DOMDocument;
use DOMElement;


final class RemoveCacheResultFileAttribute implements Migration
{
    public function migrate(DOMDocument $document): void
    {
        $root = $document->documentElement;

        assert($root instanceof DOMElement);

        if ($root->hasAttribute('cacheResultFile')) {
            $root->removeAttribute('cacheResultFile');
        }
    }
}
