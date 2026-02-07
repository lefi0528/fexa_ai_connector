<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use DOMDocument;
use DOMElement;
use DOMXPath;


final class RemoveLoggingElements implements Migration
{
    public function migrate(DOMDocument $document): void
    {
        $this->removeTestDoxElement($document);
        $this->removeTextElement($document);
    }

    private function removeTestDoxElement(DOMDocument $document): void
    {
        $node = (new DOMXPath($document))->query('logging/testdoxXml')->item(0);

        if (!$node instanceof DOMElement || $node->parentNode === null) {
            return;
        }

        $node->parentNode->removeChild($node);
    }

    private function removeTextElement(DOMDocument $document): void
    {
        $node = (new DOMXPath($document))->query('logging/text')->item(0);

        if (!$node instanceof DOMElement || $node->parentNode === null) {
            return;
        }

        $node->parentNode->removeChild($node);
    }
}
