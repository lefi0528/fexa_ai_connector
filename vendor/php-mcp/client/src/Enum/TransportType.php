<?php

declare(strict_types=1);

namespace PhpMcp\Client\Enum;


enum TransportType: string
{
    case Stdio = 'stdio';
    case Http = 'http';
}
