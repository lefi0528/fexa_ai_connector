<?php

namespace PhpMcp\Server\Tests\Unit\Attributes;

use PhpMcp\Server\Attributes\McpResource;

it('instantiates with correct properties', function () {
    
    $uri = 'file:///test/resource';
    $name = 'test-resource-name';
    $description = 'This is a test resource description.';
    $mimeType = 'text/plain';
    $size = 1024;

    
    $attribute = new McpResource(
        uri: $uri,
        name: $name,
        description: $description,
        mimeType: $mimeType,
        size: $size,
    );

    
    expect($attribute->uri)->toBe($uri);
    expect($attribute->name)->toBe($name);
    expect($attribute->description)->toBe($description);
    expect($attribute->mimeType)->toBe($mimeType);
    expect($attribute->size)->toBe($size);
});

it('instantiates with null values for name and description', function () {
    
    $attribute = new McpResource(
        uri: 'file:///test', 
        name: null,
        description: null,
        mimeType: null,
        size: null,
    );

    
    expect($attribute->uri)->toBe('file:///test');
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
    expect($attribute->mimeType)->toBeNull();
    expect($attribute->size)->toBeNull();
});

it('instantiates with missing optional arguments', function () {
    
    $uri = 'file:///only-uri';
    $attribute = new McpResource(uri: $uri);

    
    expect($attribute->uri)->toBe($uri);
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
    expect($attribute->mimeType)->toBeNull();
    expect($attribute->size)->toBeNull();
});
