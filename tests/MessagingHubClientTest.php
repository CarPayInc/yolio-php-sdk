<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MessagingHub\Sdk\ClientConfig;
use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;
use MessagingHub\Sdk\MessagingHubClient;
use MessagingHub\Sdk\Resources\CampaignResource;
use MessagingHub\Sdk\Resources\MessageLogResource;
use MessagingHub\Sdk\Resources\MessageResource;
use MessagingHub\Sdk\Resources\TemplateResource;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the Messaging Hub PHP SDK.
 *
 * Uses Guzzle's MockHandler to simulate API responses and verify
 * that the SDK correctly handles success and error scenarios.
 */
class MessagingHubClientTest extends TestCase
{
    /**
     * Create a MessageResource with a mocked Guzzle client.
     *
     * @param MockHandler $mock The mock handler pre-loaded with responses
     *
     * @return MessageResource
     */
    private function createMessageResource(MockHandler $mock): MessageResource
    {
        $handlerStack = HandlerStack::create($mock);
        $client       = new Client([
            'handler'  => $handlerStack,
            'base_uri' => 'https://hub.example.com/',
            'headers'  => [
                'Authorization' => 'Bearer test-api-key',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);

        return new MessageResource($client);
    }

    /**
     * Create a CampaignResource with a mocked Guzzle client.
     *
     * @param MockHandler $mock The mock handler pre-loaded with responses
     *
     * @return CampaignResource
     */
    private function createCampaignResource(MockHandler $mock): CampaignResource
    {
        $handlerStack = HandlerStack::create($mock);
        $client       = new Client([
            'handler'  => $handlerStack,
            'base_uri' => 'https://hub.example.com/',
            'headers'  => [
                'Authorization' => 'Bearer test-api-key',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
        ]);

        return new CampaignResource($client);
    }

    /**
     * Test that sending a message returns the expected response.
     *
     * @return void
     */
    public function testSuccessfulSendReturnsResponse(): void
    {
        $responseBody = [
            'id'      => 42,
            'status'  => 'queued',
            'message' => 'Message dispatched successfully.',
        ];

        $mock    = new MockHandler([
            new Response(202, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $result = $resource->send('welcome-email', 'email', 'user@example.com', [
            'data' => ['name' => 'John'],
        ]);

        $this->assertSame(42, $result['id']);
        $this->assertSame('queued', $result['status']);
        $this->assertSame('Message dispatched successfully.', $result['message']);
    }

    /**
     * Test that queuing a message returns a response with an ID.
     *
     * @return void
     */
    public function testSuccessfulQueueReturnsResponseWithId(): void
    {
        $responseBody = [
            'id'           => 99,
            'status'       => 'scheduled',
            'scheduled_at' => '2026-03-01T10:00:00Z',
        ];

        $mock    = new MockHandler([
            new Response(202, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $result = $resource->queue(
            'payment-reminder',
            'sms',
            '+1234567890',
            '2026-03-01T10:00:00Z',
            ['data' => ['amount' => '49.99']]
        );

        $this->assertArrayHasKey('id', $result);
        $this->assertSame(99, $result['id']);
        $this->assertSame('scheduled', $result['status']);
        $this->assertSame('2026-03-01T10:00:00Z', $result['scheduled_at']);
    }

    /**
     * Test that listing campaigns returns paginated data.
     *
     * @return void
     */
    public function testListCampaignsReturnsPaginatedData(): void
    {
        $responseBody = [
            'data' => [
                ['id' => 1, 'key' => 'welcome-email', 'name' => 'Welcome Email'],
                ['id' => 2, 'key' => 'payment-reminder', 'name' => 'Payment Reminder'],
            ],
            'meta' => [
                'current_page' => 1,
                'per_page'     => 15,
                'total'        => 2,
            ],
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $result = $resource->list(['active' => true]);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);
        $this->assertSame('welcome-email', $result['data'][0]['key']);
        $this->assertSame(1, $result['meta']['current_page']);
    }

    /**
     * Test that creating a campaign returns the created object.
     *
     * @return void
     */
    public function testCreateCampaignReturnsCreatedObject(): void
    {
        $responseBody = [
            'id'           => 3,
            'key'          => 'order-confirmation',
            'name'         => 'Order Confirmation',
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email', 'sms'],
            'active'       => true,
        ];

        $mock    = new MockHandler([
            new Response(201, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $result = $resource->create([
            'key'          => 'order-confirmation',
            'name'         => 'Order Confirmation',
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email', 'sms'],
        ]);

        $this->assertSame(3, $result['id']);
        $this->assertSame('order-confirmation', $result['key']);
        $this->assertSame('Order Confirmation', $result['name']);
        $this->assertTrue($result['active']);
    }

    /**
     * Test that getting a campaign returns the campaign with its templates.
     *
     * @return void
     */
    public function testGetCampaignReturnsCampaignWithTemplates(): void
    {
        $responseBody = [
            'id'        => 1,
            'key'       => 'welcome-email',
            'name'      => 'Welcome Email',
            'templates' => [
                ['id' => 10, 'channel' => 'email', 'locale' => 'en'],
                ['id' => 11, 'channel' => 'email', 'locale' => 'es'],
            ],
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $result = $resource->get(1);

        $this->assertSame(1, $result['id']);
        $this->assertSame('welcome-email', $result['key']);
        $this->assertArrayHasKey('templates', $result);
        $this->assertCount(2, $result['templates']);
        $this->assertSame('en', $result['templates'][0]['locale']);
        $this->assertSame('es', $result['templates'][1]['locale']);
    }

    /**
     * Test that a 401 response throws an AuthenticationException.
     *
     * @return void
     */
    public function testUnauthorizedThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);

        $mock = new MockHandler([
            new Response(401, [], json_encode([
                'message' => 'Unauthenticated.',
            ])),
        ]);
        $resource = $this->createCampaignResource($mock);

        $resource->list();
    }

    /**
     * Test that a 422 response throws a ValidationException with errors.
     *
     * @return void
     */
    public function testValidationErrorThrowsValidationException(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'campaign_key'      => ['The campaign key field is required.'],
                    'recipient_address' => ['The recipient address field is required.'],
                ],
            ])),
        ]);
        $resource = $this->createMessageResource($mock);

        try {
            $resource->send('', 'email', '');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('The given data was invalid.', $e->getMessage());

            $errors = $e->getErrors();
            $this->assertArrayHasKey('campaign_key', $errors);
            $this->assertArrayHasKey('recipient_address', $errors);
            $this->assertSame('The campaign key field is required.', $errors['campaign_key'][0]);
        }
    }

    /**
     * Test that a 500 response throws an ApiException.
     *
     * @return void
     */
    public function testServerErrorThrowsApiException(): void
    {
        $this->expectException(ApiException::class);

        $mock = new MockHandler([
            new Response(500, [], json_encode([
                'message' => 'Internal Server Error',
            ])),
        ]);
        $resource = $this->createCampaignResource($mock);

        try {
            $resource->list();
        } catch (ApiException $e) {
            $this->assertSame(500, $e->getStatusCode());

            throw $e;
        }
    }

    /**
     * Test that the static factory creates a functional client instance.
     *
     * @return void
     */
    public function testStaticFactoryCreatesClient(): void
    {
        $client = MessagingHubClient::create('https://hub.example.com', 'test-key');

        $this->assertInstanceOf(MessagingHubClient::class, $client);
        $this->assertInstanceOf(CampaignResource::class, $client->campaigns());
        $this->assertInstanceOf(TemplateResource::class, $client->templates());
        $this->assertInstanceOf(MessageResource::class, $client->messages());
        $this->assertInstanceOf(MessageLogResource::class, $client->messageLog());
    }
}
