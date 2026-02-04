<?php

/**
 * Copyright (c) 2025 Fexa AI
 *
 * All Rights Reserved.
 *
 * This module is proprietary software owned by Fexa AI. All intellectual property rights, including copyrights, trademarks, and trade secrets, are reserved by Fexa AI.
 */

namespace PrestaShop\Module\FexaAiConnector\Http;

if (!defined('_PS_VERSION_')) {
    exit;
}

class HttpConstants
{
    public const CONTENT_TYPE_JSON = 'application/json';

    public const CONTENT_TYPE_SSE = 'text/event-stream';

    public const HEADER_CONTENT_TYPE = 'Content-Type';

    public const HEADER_ACCEPT = 'Accept';

    public const HEADER_AUTHORIZATION = 'Authorization';

    public const JSON_CONTENT_TYPE_HEADER = 'Content-Type: application/json';

    public const SSE_CONTENT_TYPE_HEADER = 'Content-Type: text/event-stream';
}
