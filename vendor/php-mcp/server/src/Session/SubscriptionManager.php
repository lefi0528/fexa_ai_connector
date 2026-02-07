<?php

namespace PhpMcp\Server\Session;

use Psr\Log\LoggerInterface;

class SubscriptionManager
{
    
    private array $resourceSubscribers = [];

    
    private array $sessionSubscriptions = [];

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    
    public function subscribe(string $sessionId, string $uri): void
    {
        
        $this->resourceSubscribers[$uri][$sessionId] = true;
        $this->sessionSubscriptions[$sessionId][$uri] = true;

        $this->logger->debug('Session subscribed to resource', [
            'sessionId' => $sessionId,
            'uri' => $uri
        ]);
    }

    
    public function unsubscribe(string $sessionId, string $uri): void
    {
        unset($this->resourceSubscribers[$uri][$sessionId]);
        unset($this->sessionSubscriptions[$sessionId][$uri]);

        
        if (empty($this->resourceSubscribers[$uri])) {
            unset($this->resourceSubscribers[$uri]);
        }

        $this->logger->debug('Session unsubscribed from resource', [
            'sessionId' => $sessionId,
            'uri' => $uri
        ]);
    }

    
    public function getSubscribers(string $uri): array
    {
        return array_keys($this->resourceSubscribers[$uri] ?? []);
    }

    
    public function isSubscribed(string $sessionId, string $uri): bool
    {
        return isset($this->sessionSubscriptions[$sessionId][$uri]);
    }

    
    public function cleanupSession(string $sessionId): void
    {
        if (!isset($this->sessionSubscriptions[$sessionId])) {
            return;
        }

        $uris = array_keys($this->sessionSubscriptions[$sessionId]);
        foreach ($uris as $uri) {
            unset($this->resourceSubscribers[$uri][$sessionId]);

            
            if (empty($this->resourceSubscribers[$uri])) {
                unset($this->resourceSubscribers[$uri]);
            }
        }

        unset($this->sessionSubscriptions[$sessionId]);

        $this->logger->debug('Cleaned up all subscriptions for session', [
            'sessionId' => $sessionId,
            'count' => count($uris)
        ]);
    }
}
