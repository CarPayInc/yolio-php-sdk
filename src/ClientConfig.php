<?php

declare(strict_types=1);

namespace MessagingHub\Sdk;

/**
 * Configuration value object for the Messaging Hub API client.
 *
 * Holds the base URL, API key, and timeout settings required
 * to communicate with the Messaging Hub API.
 */
class ClientConfig
{
    /** @var string The base URL of the Messaging Hub API (e.g. https://hub.example.com) */
    private string $baseUrl;

    /** @var string The Bearer token used to authenticate API requests */
    private string $apiKey;

    /** @var int HTTP request timeout in seconds */
    private int $timeout;

    /**
     * Create a new client configuration.
     *
     * @param string $baseUrl The base URL of the Messaging Hub API (no trailing slash)
     * @param string $apiKey  The API key (Bearer token) for authentication
     * @param int    $timeout HTTP request timeout in seconds (default: 30)
     */
    public function __construct(string $baseUrl, string $apiKey, int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey  = $apiKey;
        $this->timeout = $timeout;
    }

    /**
     * Get the base URL of the Messaging Hub API.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the API key used for authentication.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the HTTP request timeout in seconds.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
