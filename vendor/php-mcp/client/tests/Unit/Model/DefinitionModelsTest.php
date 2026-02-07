<?php

use PhpMcp\Client\Exception\ProtocolException;
use PhpMcp\Client\Model\Definitions\PromptArgumentDefinition;
use PhpMcp\Client\Model\Definitions\PromptDefinition;
use PhpMcp\Client\Model\Definitions\ResourceDefinition;
use PhpMcp\Client\Model\Definitions\ResourceTemplateDefinition;
use PhpMcp\Client\Model\Definitions\ToolDefinition;



it('creates tool definition and converts to/from array', function () {
    
    $name = 'my-tool_1';
    $description = 'Does something cool.';
    $schema = ['type' => 'object', 'properties' => ['arg1' => ['type' => 'string']]];
    $tool = new ToolDefinition($name, $description, $schema);

    
    $array = $tool->toArray();
    $rehydrated = ToolDefinition::fromArray($array);

    
    expect($tool->name)->toBe($name);
    expect($tool->description)->toBe($description);
    expect($tool->inputSchema)->toBe($schema);
    expect($array)->toBe([
        'name' => $name,
        'description' => $description,
        'inputSchema' => $schema,
    ]);
    expect($rehydrated)->toEqual($tool);
});

it('handles null description in tool definition', function () {
    $tool = new ToolDefinition('tool-no-desc', null, ['type' => 'object']);
    $array = $tool->toArray();
    $rehydrated = ToolDefinition::fromArray($array);

    expect($tool->description)->toBeNull();
    expect($array['description'])->toBeNull();
    expect($rehydrated->description)->toBeNull();
});

it('validates tool name pattern', function ($name, $isValid) {
    if ($isValid) {
        $tool = new ToolDefinition($name, null, []);
        expect($tool->name)->toBe($name); 
    } else {
        expect(fn () => new ToolDefinition($name, null, []))->toThrow(InvalidArgumentException::class);
    }
})->with([
    ['valid-tool', true],
    ['valid_tool', true],
    ['tool123', true],
    ['tool-1_2', true],
    ['invalid name', false], 
    ['invalid!', false],     
    ['', false],             
]);

it('throws protocol exception on invalid tool definition from array', function (array $data) {
    ToolDefinition::fromArray($data);
})->throws(ProtocolException::class)->with([
    [['description' => 'd']], 
    [['name' => 123]], 
    [['name' => 'n', 'inputSchema' => 'not-an-array']], 
]);



it('creates resource definition and converts to/from array', function () {
    
    $uri = 'file:///path/to/resource.txt';
    $name = 'my-resource';
    $description = 'A test resource file.';
    $mime = 'text/plain';
    $size = 1024;
    $annotations = ['scope' => 'user'];
    $res = new ResourceDefinition($uri, $name, $description, $mime, $size, $annotations);

    
    $array = $res->toArray();
    $rehydrated = ResourceDefinition::fromArray($array);

    
    expect($res->uri)->toBe($uri);
    expect($res->name)->toBe($name);
    expect($res->description)->toBe($description);
    expect($res->mimeType)->toBe($mime);
    expect($res->size)->toBe($size);
    expect($res->annotations)->toBe($annotations);
    expect($array)->toBe([
        'uri' => $uri,
        'name' => $name,
        'description' => $description,
        'mimeType' => $mime,
        'size' => $size,
        'annotations' => $annotations,
    ]);
    expect($rehydrated)->toEqual($res);
});

it('handles optional fields in resource definition', function () {
    $res = new ResourceDefinition('test://uri', 'test-name', null, null, null, []);
    $array = $res->toArray();
    $rehydrated = ResourceDefinition::fromArray($array);

    expect($res->description)->toBeNull();
    expect($res->mimeType)->toBeNull();
    expect($res->size)->toBeNull();
    expect($res->annotations)->toBe([]);
    expect($array['description'])->toBeNull();
    expect($array['mimeType'])->toBeNull();
    expect($array['size'])->toBeNull();
    expect($array['annotations'])->toBe([]);
    expect($rehydrated)->toEqual($res);
});

it('throws protocol exception on invalid resource definition from array', function (array $data) {
    ResourceDefinition::fromArray($data);
})->throws(ProtocolException::class)->with([
    [['name' => 'n']], 
    [['uri' => 'u']], 
    [['uri' => 'u', 'name' => 123]], 
    [['uri' => 'u', 'name' => 'n', 'size' => 'big']], 
    [['uri' => 'u', 'name' => 'n', 'annotations' => 'not-array']], 
]);



it('creates resource template definition and converts to/from array', function () {
    
    $uriTemplate = 'user://{userId}/data';
    $name = 'user-data';
    $description = 'User data template.';
    $mime = 'application/json';
    $annotations = ['dynamic' => true];
    $tmpl = new ResourceTemplateDefinition($uriTemplate, $name, $description, $mime, $annotations);

    
    $array = $tmpl->toArray();
    $rehydrated = ResourceTemplateDefinition::fromArray($array);

    
    expect($tmpl->uriTemplate)->toBe($uriTemplate);
    expect($array)->toBe([
        'uriTemplate' => $uriTemplate,
        'name' => $name,
        'description' => $description,
        'mimeType' => $mime,
        'annotations' => $annotations,
    ]);
    expect($rehydrated)->toEqual($tmpl);
});



it('creates prompt definition and converts to/from array', function () {
    
    $arg1 = new PromptArgumentDefinition('topic', 'The main topic', true);
    $arg2 = new PromptArgumentDefinition('style', 'Writing style', false);
    $prompt = new PromptDefinition('gen-story', 'Generates a short story', [$arg1, $arg2]);

    
    $array = $prompt->toArray();
    $rehydrated = PromptDefinition::fromArray($array);

    
    expect($prompt->name)->toBe('gen-story');
    expect($prompt->description)->toBe('Generates a short story');
    expect($prompt->arguments)->toHaveCount(2)->toEqual([$arg1, $arg2]);
    expect($array)->toBe([
        'name' => 'gen-story',
        'description' => 'Generates a short story',
        'arguments' => [$arg1->toArray(), $arg2->toArray()],
    ]);
    expect($rehydrated)->toEqual($prompt); 
});

it('handles prompt definition with no arguments', function () {
    $prompt = new PromptDefinition('simple-prompt', 'A simple prompt', []);
    $array = $prompt->toArray();
    $rehydrated = PromptDefinition::fromArray($array);

    expect($prompt->arguments)->toBe([]);
    expect($array['arguments'])->toBe([]);
    expect($rehydrated->arguments)->toBe([]);
});



it('creates prompt argument definition and converts to/from array', function () {
    
    $argReq = new PromptArgumentDefinition('name', 'User name', true);
    $argOpt = new PromptArgumentDefinition('format', null, false);

    
    $arrayReq = $argReq->toArray();
    $arrayOpt = $argOpt->toArray();
    $rehydratedReq = PromptArgumentDefinition::fromArray($arrayReq);
    $rehydratedOpt = PromptArgumentDefinition::fromArray($arrayOpt);

    
    expect($argReq->name)->toBe('name');
    expect($argReq->description)->toBe('User name');
    expect($argReq->required)->toBeTrue();
    expect($arrayReq)->toEqual(['name' => 'name', 'description' => 'User name', 'required' => true]);
    expect($rehydratedReq)->toEqual($argReq);

    
    expect($argOpt->name)->toBe('format');
    expect($argOpt->description)->toBeNull();
    expect($argOpt->required)->toBeFalse();
    expect($arrayOpt)->toEqual(['name' => 'format', 'required' => false]);
    expect($rehydratedOpt)->toEqual($argOpt);
});
