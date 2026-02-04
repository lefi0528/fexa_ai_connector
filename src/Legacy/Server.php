<?php
/**
 * Legacy MCP Server Implementation for PHP 7.4
 * Supports a subset of MCP features required for SEO Copilot.
 */

namespace PrestaShop\Module\FexaAiConnector\Legacy;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Server
{
    private $tools = [];

    public function __construct()
    {
        $this->registerTool('list_products', 'PrestaShop\Module\FexaAiConnector\Legacy\Tools\ProductTool::listProducts');
        $this->registerTool('get_product', 'PrestaShop\Module\FexaAiConnector\Legacy\Tools\ProductTool::getProduct');
        $this->registerTool('update_product', 'PrestaShop\Module\FexaAiConnector\Legacy\Tools\ProductTool::updateProduct');
    }

    public function registerTool($name, $callable)
    {
        $this->tools[$name] = $callable;
    }

    public function handleRequest($requestFunc)
    {
        // Simple JSON-RPC 2.0 implementation
        $rawInput = file_get_contents('php://input');
        $request = json_decode($rawInput, true);

        if (!$request || !isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0') {
            $this->sendError(null, -32600, 'Invalid Request');
            return;
        }

        $id = isset($request['id']) ? $request['id'] : null;
        $method = isset($request['method']) ? $request['method'] : '';
        $params = isset($request['params']) ? $request['params'] : [];

        switch ($method) {
            case 'tools/list':
                $this->sendResponse($id, [
                    'tools' => [
                        [
                            'name' => 'list_products',
                            'description' => 'List products with pagination',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'page' => ['type' => 'integer'],
                                    'limit' => ['type' => 'integer']
                                ]
                            ]
                        ],
                        [
                            'name' => 'get_product',
                            'description' => 'Get product details',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer']
                                ]
                            ]
                        ],
                        [
                            'name' => 'update_product',
                            'description' => 'Update product fields',
                            'inputSchema' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'description_short' => ['type' => 'string'],
                                    'description' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]);
                break;

            case 'tools/call':
                $toolName = isset($params['name']) ? $params['name'] : '';
                $toolArgs = isset($params['arguments']) ? $params['arguments'] : [];

                if (isset($this->tools[$toolName])) {
                    try {
                        $result = call_user_func($this->tools[$toolName], $toolArgs);
                        $this->sendResponse($id, ['content' => [['type' => 'text', 'text' => json_encode($result)]]]);
                    } catch (\Exception $e) {
                         $this->sendError($id, -32603, 'Internal error: ' . $e->getMessage());
                    }
                } else {
                    $this->sendError($id, -32601, 'Method not found');
                }
                break;
            
            // Add other MCP protocol handshake methods if strict compliance required?
            // "initialize", "notifications/initialized"
            case 'initialize':
                $this->sendResponse($id, [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => ['listChanged' => false]
                    ],
                    'serverInfo' => [
                        'name' => 'SEO Copilot Legacy',
                        'version' => '1.0.0'
                    ]
                ]);
                break;
            
            case 'notifications/initialized':
                // No response needed for notifications
                break;

            default:
                $this->sendError($id, -32601, 'Method not found: ' . $method);
                break;
        }
    }

    private function sendResponse($id, $result)
    {
        if ($id === null) return;
        header('Content-Type: application/json');
        echo json_encode([
            'jsonrpc' => '2.0',
            'result' => $result,
            'id' => $id
        ]);
        exit;
    }

    private function sendError($id, $code, $message)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'id' => $id
        ]);
        exit;
    }
}
