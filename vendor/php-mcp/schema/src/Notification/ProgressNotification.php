<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Notification;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\JsonRpc\Notification;


class ProgressNotification extends Notification
{
    
    public function __construct(
        public readonly string|int $progressToken,
        public readonly float $progress,
        public readonly ?float $total = null,
        public readonly ?string $message = null,
        public readonly ?array $_meta = null
    ) {
        $params = [];
        if ($_meta !== null) {
            $params['_meta'] = $_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, 'notifications/progress', $params);
    }

    
    public static function make(string|int $progressToken, float $progress, ?float $total = null, ?string $message = null, ?array $_meta = null): static
    {
        return new static($progressToken, $progress, $total, $message, $_meta);
    }

    public static function fromNotification(Notification $notification): static
    {
        if ($notification->method !== 'notifications/progress') {
            throw new \InvalidArgumentException('Notification is not a notifications/progress notification');
        }

        $params = $notification->params;

        if (! isset($params['progressToken']) || ! is_string($params['progressToken'])) {
            throw new \InvalidArgumentException('Missing or invalid progressToken parameter for notifications/progress notification');
        }

        if (! isset($params['progress']) || ! is_float($params['progress'])) {
            throw new \InvalidArgumentException('Missing or invalid progress parameter for notifications/progress notification');
        }

        return new static($params['progressToken'], $params['progress'], $params['total'] ?? null, $params['message'] ?? null, $params['_meta'] ?? null);
    }
}
