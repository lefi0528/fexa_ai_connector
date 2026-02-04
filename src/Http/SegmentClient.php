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

const SEGMENT_URL = 'https://api.segment.io/v1/';

class SegmentClient
{
    private Client $httpClient;
    private string $writeKey;
    private static string $trackEndpoint = SEGMENT_URL . 'track';

    public function __construct(string $writeKey, ?Client $httpClient)
    {
        $this->httpClient = $httpClient ?? Client::getInstance();
        $this->writeKey = $writeKey;
    }

    public function track(array $data): array
    {
        $headers = [
            HttpConstants::HEADER_ACCEPT => HttpConstants::CONTENT_TYPE_JSON,
            HttpConstants::HEADER_CONTENT_TYPE => HttpConstants::CONTENT_TYPE_JSON,
        ];
        $data['writeKey'] = $this->writeKey;

        $request = $this->httpClient->post(self::$trackEndpoint, $headers, $data);

        return [
            'success' => substr((string) $request->getHttpStatus(), 0, 1) === '2',
            'httpCode' => $request->getHttpStatus(),
            'body' => $request->getResponse(),
        ];
    }
}
