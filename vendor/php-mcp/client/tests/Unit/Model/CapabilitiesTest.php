<?php

use PhpMcp\Client\Model\Capabilities;

it('creates client capabilities correctly', function () {
    
    $capsDefault = Capabilities::forClient(); 
    $capsNoSampling = Capabilities::forClient(supportsSampling: false);
    $capsWithRoots = Capabilities::forClient(supportsSampling: true, supportsRootListChanged: true);
    $capsWithRootsNoChange = Capabilities::forClient(supportsSampling: false, supportsRootListChanged: false);
    $capsWithExperimental = Capabilities::forClient(experimental: ['myFeature' => true]);

    
    expect($capsDefault->sampling)->toBe([]);
    expect($capsDefault->roots)->toBeNull();
    expect($capsDefault->experimental)->toBeNull();
    expect($capsDefault->toClientArray())->toEqual(['sampling' => new stdClass]); 

    
    expect($capsNoSampling->sampling)->toBeNull();
    expect($capsNoSampling->toClientArray())->toEqual(new stdClass); 

    
    expect($capsWithRoots->sampling)->toBe([]);
    expect($capsWithRoots->roots)->toBe(['listChanged' => true]);
    expect($capsWithRoots->toClientArray())->toEqual([
        'roots' => ['listChanged' => true],
        'sampling' => new stdClass,
    ]);

    
    expect($capsWithRootsNoChange->sampling)->toBeNull();
    expect($capsWithRootsNoChange->roots)->toBe(['listChanged' => false]);
    expect($capsWithRootsNoChange->toClientArray())->toEqual([
        'roots' => ['listChanged' => false],
        
    ]);

    
    expect($capsWithExperimental->experimental)->toBe(['myFeature' => true]);
    expect($capsWithExperimental->toClientArray())->toEqual([
        'sampling' => new stdClass,
        'experimental' => ['myFeature' => true],
    ]);

});

it('parses server capabilities correctly', function () {
    
    $serverResponseData = [
        
        'roots' => ['listChanged' => true],
        'sampling' => [],
        
        'tools' => ['listChanged' => true],
        'resources' => ['subscribe' => true, 'listChanged' => false],
        'prompts' => ['listChanged' => false],
        'logging' => [], 
        'experimental' => ['serverFeature' => 'beta'],
    ];

    
    $caps = Capabilities::fromServerResponse($serverResponseData);

    
    expect($caps->tools)->toBe(['listChanged' => true]);
    expect($caps->resources)->toBe(['subscribe' => true, 'listChanged' => false]);
    expect($caps->prompts)->toBe(['listChanged' => false]);
    expect($caps->logging)->toBe([]);
    expect($caps->experimental)->toBe(['serverFeature' => 'beta']);

    
    expect($caps->roots)->toBeNull();
    expect($caps->sampling)->toBeNull();

    
    expect($caps->serverSupportsTools())->toBeTrue();
    expect($caps->serverSupportsToolListChanged())->toBeTrue();
    expect($caps->serverSupportsResources())->toBeTrue();
    expect($caps->serverSupportsResourceSubscription())->toBeTrue();
    expect($caps->serverSupportsResourceListChanged())->toBeFalse();
    expect($caps->serverSupportsPrompts())->toBeTrue();
    expect($caps->serverSupportsPromptListChanged())->toBeFalse();
    expect($caps->serverSupportsLogging())->toBeTrue(); 
});

it('handles missing server capabilities gracefully', function () {
    
    $serverResponseData = [
        'tools' => ['listChanged' => true],
        
    ];

    
    $caps = Capabilities::fromServerResponse($serverResponseData);

    
    expect($caps->tools)->toBe(['listChanged' => true]);
    expect($caps->resources)->toBeNull();
    expect($caps->prompts)->toBeNull();
    expect($caps->logging)->toBeNull();
    expect($caps->experimental)->toBeNull();

    
    expect($caps->serverSupportsTools())->toBeTrue();
    expect($caps->serverSupportsResources())->toBeFalse();
    expect($caps->serverSupportsResourceSubscription())->toBeFalse();
    
});
