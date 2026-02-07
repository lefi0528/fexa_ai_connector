<?php

namespace PhpMcp\Server\Tests\Unit\Attributes;

use PhpMcp\Server\Attributes\McpTool;

it('instantiates with correct properties', function () {
    
    $name = 'test-tool-name';
    $description = 'This is a test description.';

    
    $attribute = new McpTool(name: $name, description: $description);

    
    expect($attribute->name)->toBe($name);
    expect($attribute->description)->toBe($description);
});

it('instantiates with null values for name and description', function () {
    
    $attribute = new McpTool(name: null, description: null);

    
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
});

it('instantiates with missing optional arguments', function () {
    
    $attribute = new McpTool(); 

    
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
});
