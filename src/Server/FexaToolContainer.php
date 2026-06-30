<?php
/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI.
 *
 * @author    Fexa AI <support@fexaai.com>
 * @copyright 2025 Fexa AI
 * @license   Proprietary
 */

namespace PrestaShop\Module\FexaAiConnector\Server;

use PhpMcp\Server\Defaults\BasicContainer;
use PrestaShop\Module\FexaAiConnector\Mcp\Tools\CategoryTools;
use PrestaShop\Module\FexaAiConnector\Mcp\Tools\CmsTools;
use PrestaShop\Module\FexaAiConnector\Mcp\Tools\ProductTools;
use PrestaShop\Module\FexaAiConnector\Mcp\Tools\ShopTools;
use Psr\Container\ContainerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * PSR-11 container for the MCP server.
 *
 * php-mcp resolves every tool handler through $container->get($className) at call
 * time (see PhpMcp\Server\Elements\RegisteredElement::handle). This container is
 * the single seam where the shop-aware tools receive the current PrestaShop
 * context by constructor injection — so the tool classes never reach for a global
 * accessor and stay unit-testable. Every other element falls through to php-mcp's
 * default BasicContainer, preserving the stock behaviour.
 */
class FexaToolContainer implements ContainerInterface
{
    /**
     * Tool handlers that receive the shop context through their constructor.
     */
    private const CONTEXT_AWARE_TOOLS = [
        CategoryTools::class,
        CmsTools::class,
        ProductTools::class,
        ShopTools::class,
    ];

    private BasicContainer $inner;

    /**
     * The current shop context. Untyped on purpose so the container carries no hard
     * dependency on the legacy context class; the shop-aware tools only read members.
     *
     * @var mixed
     */
    private $shopContext;

    /**
     * @param mixed $shopContext the live PrestaShop context, sourced from the front
     *                           controller (never from a global accessor)
     */
    public function __construct($shopContext = null)
    {
        $this->shopContext = $shopContext;
        $this->inner = new BasicContainer();
    }

    public function get(string $id): mixed
    {
        if (in_array($id, self::CONTEXT_AWARE_TOOLS, true)) {
            return new $id($this->shopContext);
        }

        return $this->inner->get($id);
    }

    public function has(string $id): bool
    {
        return in_array($id, self::CONTEXT_AWARE_TOOLS, true) || $this->inner->has($id);
    }
}
