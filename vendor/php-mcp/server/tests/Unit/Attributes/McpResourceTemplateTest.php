<?php

namespace PhpMcp\Server\Tests\Unit\Attributes;

use PhpMcp\Server\Attributes\McpResourceTemplate;

it('instantiates with correct properties', function () {
    
    $uriTemplate = 'file:///{path}/data';
    $name = 'test-template-name';
    $description = 'This is a test template description.';
    $mimeType = 'application/json';

    
    $attribute = new McpResourceTemplate(
        uriTemplate: $uriTemplate,
        name: $name,
        description: $description,
        mimeType: $mimeType,
    );

    
    expect($attribute->uriTemplate)->toBe($uriTemplate);
    expect($attribute->name)->toBe($name);
    expect($attribute->description)->toBe($description);
    expect($attribute->mimeType)->toBe($mimeType);
});

it('instantiates with null values for name and description', function () {
    
    $attribute = new McpResourceTemplate(
        uriTemplate: 'test://{id}', 
        name: null,
        description: null,
        mimeType: null,
    );

    
    expect($attribute->uriTemplate)->toBe('test://{id}');
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
    expect($attribute->mimeType)->toBeNull();
});

it('instantiates with missing optional arguments', function () {
    
    $uriTemplate = 'tmpl://{key}';
    $attribute = new McpResourceTemplate(uriTemplate: $uriTemplate);

    
    expect($attribute->uriTemplate)->toBe($uriTemplate);
    expect($attribute->name)->toBeNull();
    expect($attribute->description)->toBeNull();
    expect($attribute->mimeType)->toBeNull();
});
