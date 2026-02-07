<?php

namespace PhpMcp\Server\Tests\Unit\Attributes;

use PhpMcp\Server\Attributes\McpPrompt;

it('instantiates with name and description', function () {
    
    $name = 'test-prompt-name';
    $description = 'This is a test prompt description.';

    
    $attribute = new McpPrompt(name: $name, description: $description);

    
    expect($attribute->name)->toBe($name);
    expect($attribute->description)->toBe($description);
});

it('instantiates with null values for name and description', function () {
    
    $attribute = new McpPrompt(name: null, description: null);

    
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
});

it('instantiates with missing optional arguments', function () {
    
    $attribute = new McpPrompt(); 

    
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
});
