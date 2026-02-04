<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Server;

use Evenement\EventEmitterTrait;
use PhpMcp\Schema\Constants;
use PhpMcp\Schema\JsonRpc\Error;
use PhpMcp\Schema\JsonRpc\Message;
use PhpMcp\Schema\JsonRpc\Parser;
use PhpMcp\Schema\JsonRpc\Request;
use PhpMcp\Schema\JsonRpc\Response;
use PhpMcp\Server\Contracts\LoggerAwareInterface;
use PhpMcp\Server\Contracts\ServerTransportInterface;
use PrestaShop\Module\FexaAiConnector\Exceptions\ContextException;
use PrestaShop\Module\FexaAiConnector\Http\HttpConstants;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;
use PrestaShop\PrestaShop\Core\Module\ModuleManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InMemoryTransport implements ServerTransportInterface, LoggerAwareInterface
{
    use EventEmitterTrait;

    private LoggerInterface $logger;
    private bool $headersSent = false;
    private bool $useSSE = false;
    private string $sessionId = '';

    public function __construct()
    {
        $this->logger = new NullLogger();

        $context = \Context::getContext();

        if ($context === null) {
            throw new \PrestaShopException('Context is not defined');
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function listen(): void
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? null;

        $this->sessionId = $this->generateId();

        if (isset($acceptHeader)) {
            $acceptsJson = str_contains($acceptHeader, HttpConstants::CONTENT_TYPE_JSON) || str_contains($acceptHeader, '*/*');
            $acceptsSse = str_contains($acceptHeader, HttpConstants::CONTENT_TYPE_SSE) || str_contains($acceptHeader, '*/*');

            if (!$acceptsJson && !$acceptsSse) {
                $this->sendInvalidRequestError(
                    406,
                    'Not Acceptable: Client must accept application/json or text/event-stream'
                );
            }

            if ($acceptsSse) {
                $this->useSSE = true;
            }
        }

        $this->sendHeaders();

        if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
            $this->checkAuthorization();
        }

        $this->emit('ready');

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->handlePost();
                break;
            case 'GET':
                $this->handleGet();
                break;
            case 'OPTIONS':
                $this->handleOptions();
                break;
            case 'DELETE':
                $this->handleDelete();
                break;
            case 'HEAD':
                $this->handleHead();
                break;
            default:
                $this->handleUnsupportedRequest();
                break;
        }
    }

    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface
    {
        $this->sessionId = $sessionId;

        $this->sendJsonMessage($message);

        $this->close();

        return resolve(null);
    }

    public function close(): void
    {
        $this->emit('close', ['Request finished']);
        $this->removeAllListeners();
        exit;
    }

    private function handleGet(): void
    {
        http_response_code(200);
        header(HttpConstants::JSON_CONTENT_TYPE_HEADER);

        echo json_encode([
            'status' => 'ok',
            'transport' => 'http',
            'message' => 'Fexa AI Connector MCP Server is running. Please use POST for JSON-RPC messages.',
            'capabilities' => [
                'protocol_version' => '2025-06-18',
            ],
        ]);

        $this->close();
    }

    private function handlePost(): void
    {
        $contentTypeHeader = $_SERVER['HTTP_CONTENT_TYPE'] ?? null;

        if (isset($contentTypeHeader) && !str_contains($contentTypeHeader, HttpConstants::CONTENT_TYPE_JSON)) {
            $this->sendInvalidRequestError(415, 'Unsupported Media Type: Content-Type must be application/json');
        }

        $request = file_get_contents('php://input');
        if ($request === false) {
            $this->logger->warning('Received empty POST body');
            $this->sendInvalidRequestError(400, 'Empty request body.');

            return;
        }

        $message = null;

        try {
            $message = Parser::parse($request);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to parse MCP message from POST body', ['error' => $e->getMessage()]);
            $this->sendInvalidRequestError(400, 'Invalid JSON: ' . $e->getMessage());
        }

        $this->emit('client_connected', [$this->sessionId]);
        $context['stateless'] = true;

        header("Mcp-Session-Id: {$this->sessionId}");

        $this->emit('message', [$message, $this->sessionId, $context]);
    }

    private function handleHead(): void
    {
        $this->close();
    }

    private function handleDelete(): void
    {
        http_response_code(204);
        $this->close();
    }

    private function handleOptions(): void
    {
        header('Allow: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Mcp-Protocol-Version, Mcp-Session-Id');

        http_response_code(204);
        $this->close();
    }

    private function handleUnsupportedRequest(): void
    {
        header('Allow: GET, POST, DELETE, OPTIONS');
        $this->sendInvalidRequestError(405, "Method not allowed: {$_SERVER['REQUEST_METHOD']}");
    }

    private function checkAuthorization(): void
    {
        $authHeader = !empty($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] :
                     (!empty($_SERVER['HTTP_X_MCP_API_KEY']) ? $_SERVER['HTTP_X_MCP_API_KEY'] :
                     ($_REQUEST['token'] ?? null));

        $apiKey = \Configuration::get('FEXA_AI_API_KEY');
        if ($apiKey && ($authHeader === 'Bearer ' . $apiKey || $authHeader === $apiKey)) {
            return;
        }

        $this->logger->warning('Unauthorized access attempt');
        $this->sendUnauthorizeError('invalid_token', 'Invalid or missing API Key.');
    }

    private function sendHeaders(): void
    {
        if ($this->headersSent) {
            return;
        }

        header('Access-Control-Allow-Origin: *');

        if ($this->useSSE) {
            header(HttpConstants::SSE_CONTENT_TYPE_HEADER);
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        } else {
            header(HttpConstants::JSON_CONTENT_TYPE_HEADER);
        }

        header("Mcp-Session-Id: {$this->sessionId}");
        header('Mcp-protocol-version: 2025-06-18');

        $this->headersSent = true;
    }

    private function sendJsonMessage(mixed $message): void
    {
        $responseBody = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($this->useSSE) {
            echo 'data: ' . $responseBody . "\n\n";
        } else {
            echo $responseBody;
        }

        flush();
    }

    private function sendNotification(string $method, array $params = []): void
    {
        $notification = [
            'jsonrpc' => '2.0',
            'method' => $method,
        ];

        if (!empty($params)) {
            $notification['params'] = $params;
        }

        $this->sendJsonMessage($notification);
    }

    private function sendToolError(string $errorMessage): void
    {
        $resultData = [
            'content' => [
                [
                    'type' => 'text',
                    'text' => $errorMessage,
                ],
            ],
            'isError' => true,
        ];

        $message = new Response(
            Constants::JSONRPC_VERSION,
            0,
            $resultData
        );

        http_response_code(200);

        $this->sendJsonMessage($message);
        $this->close();
    }

    private function sendInvalidRequestError(int $code, string $message): void
    {
        $error = Error::forInvalidRequest($message);
        header(HttpConstants::JSON_CONTENT_TYPE_HEADER);
        http_response_code($code);
        echo json_encode($error);
        $this->close();
    }

    private function sendUnauthorizeError(string $error, string $errorDescription): void
    {
        http_response_code(401);
        header(HttpConstants::JSON_CONTENT_TYPE_HEADER);
        echo json_encode(['error' => $error, 'message' => $errorDescription]);
        $this->close();
    }
}
