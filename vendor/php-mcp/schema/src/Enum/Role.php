<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Enum;


enum Role: string
{
    case User = 'user';
    case Assistant = 'assistant';
}
