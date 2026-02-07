<?php

use PhpMcp\Client\Factory\MessageIdGenerator;

it('generates unique ids', function () {
    
    $generator = new MessageIdGenerator;

    
    $id1 = $generator->generate();
    $id2 = $generator->generate();
    $id3 = $generator->generate();

    
    expect($id1)->toBeString()->not->toBeEmpty();
    expect($id2)->toBeString()->not->toBe($id1);
    expect($id3)->toBeString()->not->toBe($id1)->not->toBe($id2);
    
    expect($id1)->toContain('-1');
    expect($id2)->toContain('-2');
    expect($id3)->toContain('-3');
});

it('generates unique ids with custom prefix', function () {
    
    $prefix = 'my-req-';
    $generator = new MessageIdGenerator($prefix);

    
    $id1 = $generator->generate();
    $id2 = $generator->generate();

    
    expect($id1)->toStartWith($prefix);
    expect($id2)->toStartWith($prefix);
    expect($id1)->not->toBe($id2);
});

it('generates unique ids across different instances', function () {
    
    $generator1 = new MessageIdGenerator;
    $generator2 = new MessageIdGenerator;

    
    $ids1 = [$generator1->generate(), $generator1->generate()];
    $ids2 = [$generator2->generate(), $generator2->generate()];

    
    expect($ids1[0])->not->toBe($ids1[1]);
    expect($ids2[0])->not->toBe($ids2[1]);
    
    expect($ids1[0])->not->toBe($ids2[0]);
    expect($ids1[1])->not->toBe($ids2[1]);
});
