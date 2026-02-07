<?php

namespace Mcp\CombinedHttpExample\Discovered;

use PhpMcp\Server\Attributes\McpResource;
use PhpMcp\Server\Attributes\McpTool;

class DiscoveredElements
{
    
    #[McpTool(name: 'discovered_status_check')]
    public function checkSystemStatus(): string
    {
        return 'System status: OK (discovered)';
    }

    
    #[McpResource(uri: 'config://priority', name: 'priority_config_discovered')]
    public function getPriorityConfigDiscovered(): string
    {
        return 'Discovered Priority Config: Low';
    }
}
