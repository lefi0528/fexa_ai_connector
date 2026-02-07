<?php declare(strict_types=1);

namespace PHPUnit\TextUI\XmlConfiguration;

use function file_get_contents;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use DOMDocument;


final class Validator
{
    public function validate(DOMDocument $document, string $xsdFilename): ValidationResult
    {
        $originalErrorHandling = libxml_use_internal_errors(true);

        $document->schemaValidateSource(file_get_contents($xsdFilename));

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($originalErrorHandling);

        return ValidationResult::fromArray($errors);
    }
}
