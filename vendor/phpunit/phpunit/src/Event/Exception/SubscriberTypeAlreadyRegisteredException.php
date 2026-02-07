<?php declare(strict_types=1);

namespace PHPUnit\Event;

use RuntimeException;


final class SubscriberTypeAlreadyRegisteredException extends RuntimeException implements Exception
{
}
