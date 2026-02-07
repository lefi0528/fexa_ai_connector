<?php declare(strict_types=1);

namespace PHPUnit\Event\Code;

use PHPUnit\Event\Exception;
use RuntimeException;


final class NoTestCaseObjectOnCallStackException extends RuntimeException implements Exception
{
    public function __construct()
    {
        parent::__construct('Cannot find TestCase object on call stack');
    }
}
