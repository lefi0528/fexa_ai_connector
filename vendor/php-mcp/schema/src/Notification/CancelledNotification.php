<?php

declare(strict_types=1);

namespace PhpMcp\Schema\Notification;

use PhpMcp\Schema\Constants;
use PhpMcp\Schema\JsonRpc\Notification;


class CancelledNotification extends Notification
{
    
    public function __construct(
        public readonly string $requestId,
        public readonly ?string $reason = null,
        public readonly ?array $_meta = null
    ) {
        $params = [
            'requestId' => $this->requestId,
        ];
        if ($this->reason !== null) {
            $params['reason'] = $this->reason;
        }
        if ($_meta !== null) {
            $params['_meta'] = $_meta;
        }

        parent::__construct(Constants::JSONRPC_VERSION, 'notifications/cancelled', $params);
    }

    
    public static function make(string $requestId, ?string $reason = null, ?array $_meta = null): static
    {
        return new static($requestId, $reason, $_meta);
    }

    public static function fromNotification(Notification $notification): static
    {
        if ($notification->method !== 'notifications/cancelled') {
            throw new \InvalidArgumentException('Notification is not a notifications/cancelled notification');
        }

        $params = $notification->params;

        if (! isset($params['requestId']) || ! is_string($params['requestId'])) {
            throw new \InvalidArgumentException('Missing or invalid requestId parameter for notifications/cancelled notification');
        }

        return new static($params['requestId'], $params['reason'] ?? null, $params['_meta'] ?? null);
    }
}
