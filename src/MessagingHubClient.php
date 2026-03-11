<?php

declare(strict_types=1);

namespace MessagingHub\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use MessagingHub\Sdk\Resources\CampaignResource;
use MessagingHub\Sdk\Resources\MessageLogResource;
use MessagingHub\Sdk\Resources\MessageResource;
use MessagingHub\Sdk\Resources\TemplateResource;

/**
 * Main entry point for the Messaging Hub PHP SDK.
 *
 * Provides access to all API resource classes for managing campaigns,
 * templates, messages, and message history. Handles Guzzle client
 * configuration and authentication header injection.
 *
 * Usage:
 *   $client = MessagingHubClient::create('https://hub.example.com', 'your-api-key');
 *   $client->messages()->send('welcome-email', 'email', 'user@example.com', [
 *       'data' => ['name' => 'John'],
 *   ]);
 */
class MessagingHubClient
{
    /** @var ClientInterface The configured Guzzle HTTP client */
    private ClientInterface $httpClient;

    /** @var CampaignResource|null Lazily instantiated campaign resource */
    private ?CampaignResource $campaignResource = null;

    /** @var TemplateResource|null Lazily instantiated template resource */
    private ?TemplateResource $templateResource = null;

    /** @var MessageResource|null Lazily instantiated message resource */
    private ?MessageResource $messageResource = null;

    /** @var MessageLogResource|null Lazily instantiated message log resource */
    private ?MessageLogResource $messageLogResource = null;

    /**
     * Create a new Messaging Hub client.
     *
     * @param ClientConfig $config The client configuration with base URL, API key, and timeout
     */
    public function __construct(ClientConfig $config)
    {
        $this->httpClient = new Client([
            'base_uri' => $config->getBaseUrl() . '/',
            'timeout'  => $config->getTimeout(),
            'headers'  => [
                'Authorization' => 'Bearer ' . $config->getApiKey(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    /**
     * Static factory method for quick client creation.
     *
     * @param string $baseUrl The base URL of the Messaging Hub API
     * @param string $apiKey  The API key for authentication
     *
     * @return self A configured client instance
     */
    public static function create(string $baseUrl, string $apiKey): self
    {
        return new self(new ClientConfig($baseUrl, $apiKey));
    }

    /**
     * Get the campaigns resource for managing campaigns.
     *
     * @return CampaignResource
     */
    public function campaigns(): CampaignResource
    {
        if ($this->campaignResource === null) {
            $this->campaignResource = new CampaignResource($this->httpClient);
        }

        return $this->campaignResource;
    }

    /**
     * Get the templates resource for managing templates.
     *
     * @return TemplateResource
     */
    public function templates(): TemplateResource
    {
        if ($this->templateResource === null) {
            $this->templateResource = new TemplateResource($this->httpClient);
        }

        return $this->templateResource;
    }

    /**
     * Get the messages resource for sending and queuing messages.
     *
     * @return MessageResource
     */
    public function messages(): MessageResource
    {
        if ($this->messageResource === null) {
            $this->messageResource = new MessageResource($this->httpClient);
        }

        return $this->messageResource;
    }

    /**
     * Get the message log resource for querying message history.
     *
     * @return MessageLogResource
     */
    public function messageLog(): MessageLogResource
    {
        if ($this->messageLogResource === null) {
            $this->messageLogResource = new MessageLogResource($this->httpClient);
        }

        return $this->messageLogResource;
    }
}
