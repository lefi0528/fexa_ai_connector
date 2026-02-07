<?php declare(strict_types=1);

namespace PHPUnit\Event;

use RuntimeException;


final class NoPreviousThrowableException extends RuntimeException implements Exception
{
}
