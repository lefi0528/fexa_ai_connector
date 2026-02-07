<?php declare(strict_types=1);

namespace PHPUnit\Event\Test;

use PHPUnit\Event\Exception;
use RuntimeException;


final class NoComparisonFailureException extends RuntimeException implements Exception
{
}
