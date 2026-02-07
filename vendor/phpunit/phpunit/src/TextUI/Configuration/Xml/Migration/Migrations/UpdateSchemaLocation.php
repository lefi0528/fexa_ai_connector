<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use function assert;
use DOMDocument;
use DOMElement;
use PHPUnit\Runner\Version;


final class UpdateSchemaLocation implements Migration
{
    public function migrate(DOMDocument $document): void
    {
        $root = $document->documentElement;

        assert($root instanceof DOMElement);

        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:noNamespaceSchemaLocation',
            'https://schema.phpunit.de/' . Version::series() . '/phpunit.xsd',
        );
    }
}
