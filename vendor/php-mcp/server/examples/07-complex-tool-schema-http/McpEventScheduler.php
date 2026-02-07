<?php

namespace Mcp\ComplexSchemaHttpExample;

use Mcp\ComplexSchemaHttpExample\Model\EventPriority;
use Mcp\ComplexSchemaHttpExample\Model\EventType;
use PhpMcp\Server\Attributes\McpTool;
use Psr\Log\LoggerInterface;

class McpEventScheduler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    
    #[McpTool(name: 'schedule_event')]
    public function scheduleEvent(
        string $title,
        string $date,
        EventType $type,
        ?string $time = null, 
        EventPriority $priority = EventPriority::Normal, 
        ?array $attendees = null, 
        bool $sendInvites = true   
    ): array {
        $this->logger->info("Tool 'schedule_event' called", compact('title', 'date', 'type', 'time', 'priority', 'attendees', 'sendInvites'));

        
        $eventDetails = [
            'title' => $title,
            'date' => $date,
            'type' => $type->value, 
            'time' => $time ?? 'All day',
            'priority' => $priority->name, 
            'attendees' => $attendees ?? [],
            'invites_will_be_sent' => ($attendees && $sendInvites),
        ];

        
        $this->logger->info('Event scheduled', ['details' => $eventDetails]);

        return [
            'success' => true,
            'message' => "Event '{$title}' scheduled successfully for {$date}.",
            'event_details' => $eventDetails,
        ];
    }
}
