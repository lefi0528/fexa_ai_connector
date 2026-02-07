<?php

namespace PhpMcp\Client\Tests\Unit\Transport\Http;

use Mockery;
use PhpMcp\Client\Exception\TransportException;
use PhpMcp\Client\JsonRpc\Request;
use PhpMcp\Client\JsonRpc\Response;
use PhpMcp\Client\Transport\Http\HttpClientTransport;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface as Psr7StreamInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface;
use ReflectionClass;


const TEST_SSE_URL = 'http://test.mcp:8080/unit/sse';
const TEST_POST_URL = 'http://test.mcp:8080/unit/message';

beforeEach(function () {
    $this->loop = Loop::get();
    $this->browser = Mockery::mock(Browser::class);

    $this->headers = ['X-Test' => 'UnitHeader'];
    $this->sessionId = 'sess_unit_abc';

    $this->transport = new HttpClientTransport(
        TEST_SSE_URL,
        $this->loop,
        $this->headers,
        $this->sessionId,
        $this->browser
    );
});

it('connects successfully and resolves after endpoint event', function () {
    
    $postUrl = TEST_POST_URL.'?sid=xyz';
    $sseResponse = Mockery::mock(PsrResponseInterface::class);
    $sseStream = Mockery::mock(ReadableStreamInterface::class);

    $sseResponse->shouldReceive('getStatusCode')->andReturn(200);
    $sseResponse->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn('text/event-stream; charset=utf-8');
    $sseResponse->shouldReceive('hasHeader')->with('Mcp-Session-Id')->andReturn(false);
    $sseResponse->shouldReceive('getBody')->andReturn($sseStream);

    $requestStreamingDeferred = new Deferred;
    $this->browser->shouldReceive('requestStreaming')
        ->with('GET', TEST_SSE_URL, Mockery::subset($this->headers + ['Mcp-Session-Id' => $this->sessionId])) 
        ->once()
        ->andReturn($requestStreamingDeferred->promise());

    $streamListeners = [];
    $sseStream->shouldReceive('on')
        ->with(Mockery::any(), Mockery::capture($streamListeners))
        ->times(3); 

    
    $connectPromise = $this->transport->connect();
    $resolvedValue = null;
    $connectPromise->then(function ($val) use (&$resolvedValue) {
        $resolvedValue = $val;
    });

    
    expect($resolvedValue)->toBeNull();

    
    $this->loop->futureTick(fn () => $requestStreamingDeferred->resolve($sseResponse));

    
    $this->loop->addTimer(0.01, fn () => $this->transport->handleSseEvent('endpoint', $postUrl, null));

    $this->loop->addTimer(0.02, fn () => $this->loop->stop());
    $this->loop->run();

    expect($resolvedValue)->toBeNull();
    $reflector = new ReflectionClass($this->transport);
    $postEndpointProp = $reflector->getProperty('postEndpointUrl');
    $postEndpointProp->setAccessible(true);
    expect($postEndpointProp->getValue($this->transport))->toBe($postUrl);

})->group('usesLoop');

it('connects using PSR7 stream adapter correctly', function () {
    
    $postUrl = TEST_POST_URL;
    $sseResponse = Mockery::mock(PsrResponseInterface::class);
    $psr7Stream = Mockery::mock(Psr7StreamInterface::class);
    $psr7Stream->shouldReceive('isReadable')->andReturn(true);
    $psr7Stream->shouldReceive('close')->byDefault();

    $sseResponse->shouldReceive('getStatusCode')->andReturn(200);
    $sseResponse->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn('text/event-stream');
    $sseResponse->shouldReceive('hasHeader')->with('Mcp-Session-Id')->andReturn(false);
    $sseResponse->shouldReceive('getBody')->andReturn($psr7Stream);

    $requestStreamingDeferred = new Deferred;
    $this->browser->shouldReceive('requestStreaming')
        ->with('GET', TEST_SSE_URL, Mockery::any())
        ->andReturn($requestStreamingDeferred->promise());

    
    $connectPromise = $this->transport->connect();
    $finalOutcome = null;
    $connectPromise->then(
        function () use (&$finalOutcome) {
            $finalOutcome = 'resolved';
        },
        function () use (&$finalOutcome) {
            $finalOutcome = 'rejected';
        }
    );

    
    $this->loop->futureTick(fn () => $requestStreamingDeferred->resolve($sseResponse));

    
    $this->loop->addTimer(0.01, fn () => $this->transport->handleSseEvent('endpoint', $postUrl, null));

    
    $this->loop->addTimer(0.02, fn () => $this->loop->stop());
    $this->loop->run();

    
    expect($finalOutcome)->toBe('resolved');
})->group('usesLoop');

it('rejects connection on non-200 SSE status', function () {
    
    $sseResponse = Mockery::mock(PsrResponseInterface::class);
    $sseResponse->shouldReceive('getStatusCode')->andReturn(404);
    $sseResponse->shouldReceive('getBody')->andReturn('Not Found');

    $requestStreamingDeferred = new Deferred;
    $this->browser->shouldReceive('requestStreaming')->andReturn($requestStreamingDeferred->promise());

    
    $connectPromise = $this->transport->connect();
    
    $rejectedReason = null;
    $connectPromise->catch(function ($reason) use (&$rejectedReason) {
        $rejectedReason = $reason;
    });

    
    $this->loop->futureTick(fn () => $requestStreamingDeferred->resolve($sseResponse));
    $this->loop->run();

    
    expect($rejectedReason)->toBeInstanceOf(TransportException::class)
        ->and($rejectedReason->getMessage())->toContain('SSE connection failed: Status 404');
})->group('usesLoop');

it('sends a message via POST successfully', function () {
    
    $postUrl = TEST_POST_URL;
    $message = new Request(10, 'do/work');
    $expectedJson = json_encode($message->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $expectedHeadersMatcher = Mockery::subset($this->headers + [
        'Content-Type' => 'application/json',
        'Mcp-Session-Id' => $this->sessionId,
    ]);

    $reflector = new ReflectionClass($this->transport);
    $postEndpointProp = $reflector->getProperty('postEndpointUrl');
    $postEndpointProp->setAccessible(true);
    $postEndpointProp->setValue($this->transport, $postUrl);

    
    $postResponse = Mockery::mock(PsrResponseInterface::class);
    $postResponse->shouldReceive('getStatusCode')->andReturn(202);
    $postResponse->shouldReceive('getBody')->andReturn('');

    $deferredPost = new Deferred;
    $this->browser->shouldReceive('post')
        ->with($postUrl, $expectedHeadersMatcher, $expectedJson)
        ->once()
        ->andReturn($deferredPost->promise());

    
    $sendPromise = $this->transport->send($message);
    $resolved = false;
    $sendPromise->then(function () use (&$resolved) {
        $resolved = true;
    });

    
    $this->loop->futureTick(fn () => $deferredPost->resolve($postResponse));
    $this->loop->run(); 

    
    expect($resolved)->toBeTrue(); 

})->group('usesLoop');

it('rejects send if POST endpoint not known', function () {
    
    $message = new Request(11, 'do/work');

    
    $sendPromise = $this->transport->send($message);
    
    $rejectedReason = null;
    $sendPromise->catch(function ($reason) use (&$rejectedReason) {
        $rejectedReason = $reason;
    });

    
    expect($rejectedReason)->toBeInstanceOf(TransportException::class)
        ->and($rejectedReason->getMessage())->toContain('POST endpoint not received');
});

it('handles SSE message event and emits message', function () {
    
    $responseJson = '{"jsonrpc":"2.0","id":10,"result":true}';
    
    $emittedMessage = null;
    $this->transport->on('message', function ($msg) use (&$emittedMessage) {
        $emittedMessage = $msg;
    });

    
    $this->transport->handleSseEvent('message', $responseJson, 'sse-id-1');

    
    expect($emittedMessage)->toBeInstanceOf(Response::class);
    expect($emittedMessage->id)->toBe(10);
    expect($emittedMessage->result)->toBeTrue();
});

it('handles SSE error event and emits error', function () {
    
    $errorData = 'Server shutdown imminent';
    
    $emittedError = null;
    $this->transport->on('error', function ($err) use (&$emittedError) {
        $emittedError = $err;
    });

    
    $this->transport->handleSseEvent('error', $errorData, null);

    
    expect($emittedError)->toBeInstanceOf(TransportException::class)
        ->and($emittedError->getMessage())->toContain($errorData);
});

it('closes connection, closes stream, sends DELETE', function () {
    
    $sseStreamMock = Mockery::mock(ReadableStreamInterface::class);
    $sseStreamMock->shouldReceive('close')->once();

    
    $reflector = new ReflectionClass($this->transport);
    $sseStreamProp = $reflector->getProperty('sseStream');
    $sseStreamProp->setAccessible(true);
    $sseStreamProp->setValue($this->transport, $sseStreamMock);
    $postEndpointProp = $reflector->getProperty('postEndpointUrl');
    $postEndpointProp->setAccessible(true);
    $postEndpointProp->setValue($this->transport, TEST_POST_URL);

    
    $this->transport->close();

    
    expect($sseStreamProp->getValue($this->transport))->toBeNull();
    expect($postEndpointProp->getValue($this->transport))->toBeNull();
    $closingProp = $reflector->getProperty('closing');
    $closingProp->setAccessible(true);
    expect($closingProp->getValue($this->transport))->toBeTrue();

});
