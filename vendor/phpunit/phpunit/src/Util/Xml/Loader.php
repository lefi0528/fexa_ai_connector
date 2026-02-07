<?php declare(strict_types=1);

namespace PHPUnit\Util\Xml;

use const PHP_OS_FAMILY;
use function chdir;
use function dirname;
use function error_reporting;
use function file_get_contents;
use function getcwd;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function sprintf;
use DOMDocument;


final class Loader
{
    
    public function loadFile(string $filename): DOMDocument
    {
        $reporting = error_reporting(0);
        $contents  = file_get_contents($filename);

        error_reporting($reporting);

        if ($contents === false) {
            throw new XmlException(
                sprintf(
                    'Could not read XML from file "%s"',
                    $filename,
                ),
            );
        }

        return $this->load($contents, $filename);
    }

    
    public function load(string $actual, ?string $filename = null): DOMDocument
    {
        if ($actual === '') {
            if ($filename === null) {
                throw new XmlException('Could not parse XML from empty string');
            }

            throw new XmlException(
                sprintf(
                    'Could not parse XML from empty file "%s"',
                    $filename,
                ),
            );
        }

        $document                     = new DOMDocument;
        $document->preserveWhiteSpace = false;

        $internal  = libxml_use_internal_errors(true);
        $message   = '';
        $reporting = error_reporting(0);

        
        if ($filename !== null) {
            
            if (PHP_OS_FAMILY === 'Windows') {
                $cwd = getcwd();
                @chdir(dirname($filename));
            }

            $document->documentURI = $filename;
        }

        $loaded = $document->loadXML($actual);

        if ($filename !== null) {
            $document->xinclude();
        }

        foreach (libxml_get_errors() as $error) {
            $message .= "\n" . $error->message;
        }

        libxml_use_internal_errors($internal);
        error_reporting($reporting);

        if (isset($cwd)) {
            @chdir($cwd);
        }

        if ($loaded === false || $message !== '') {
            if ($filename !== null) {
                throw new XmlException(
                    sprintf(
                        'Could not load "%s"%s',
                        $filename,
                        $message !== '' ? ":\n" . $message : '',
                    ),
                );
            }

            if ($message === '') {
                $message = 'Could not load XML for unknown reason';
            }

            throw new XmlException($message);
        }

        return $document;
    }
}
