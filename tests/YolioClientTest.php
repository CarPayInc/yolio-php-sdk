<?php

declare(strict_types=1);

namespace Yolio\Sdk\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Yolio\Sdk\ClientConfig;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;
use Yolio\Sdk\YolioClient;
use Yolio\Sdk\Resources\CampaignResource;
use Yolio\Sdk\Resources\EmailSubscriptions;
use Yolio\Sdk\Resources\MessageLogResource;
use Yolio\Sdk\Resources\MessageResource;
use Yolio\Sdk\Resources\TemplateResource;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for the Yolio PHP SDK.
 *
 * Uses Guzzle's MockHandler to simulate API responses and verify
 * that the SDK correctly handles success and error scenarios.
 */
class YolioClientTest extends TestCase
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
     * Create a MessageLogResource with a mocked Guzzle client.
     *
     * @param MockHandler $mock The mock handler pre-loaded with responses
     *
     * @return MessageLogResource
     */
    private function createMessageLogResource(MockHandler $mock): MessageLogResource
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

        return new MessageLogResource($client);
    }

    /**
     * Test that find() looks up a message by its ID.
     *
     * @return void
     */
    public function testFindMessageReturnsMessage(): void
    {
        $mock     = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => '01JZ8K9F3QG7N2R5T8V0W1X2Y3',
                'status'     => 'delivered',
            ])),
        ]);
        $resource = $this->createMessageLogResource($mock);

        $result = $resource->find('01JZ8K9F3QG7N2R5T8V0W1X2Y3');

        $this->assertSame('01JZ8K9F3QG7N2R5T8V0W1X2Y3', $result['message_id']);
        $this->assertSame('delivered', $result['status']);
        $this->assertSame('/api/v1/messages/01JZ8K9F3QG7N2R5T8V0W1X2Y3', $mock->getLastRequest()->getUri()->getPath());
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

        $result = $resource->send('welcome-email', 'welcome_email_en', 'user@example.com', [
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
            'payment_reminder_sms_en',
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
     * Test that send forwards email sender options and attachments in the payload.
     *
     * @return void
     */
    public function testSendForwardsEmailOptionsInPayload(): void
    {
        $mock     = new MockHandler([
            new Response(202, [], json_encode(['message' => 'queued'])),
        ]);
        $resource = $this->createMessageResource($mock);

        $resource->send('welcome-email', 'welcome_email_en', 'user@example.com', [
            'from_email'     => 'noreply@carpay.com',
            'from_name'      => 'Carpay',
            'reply_to_email' => 'support@carpay.com',
            'attachments'    => [
                ['filename' => 'invoice.pdf', 'content' => base64_encode('%PDF-1.4 body')],
            ],
        ]);

        $sent = json_decode((string) $mock->getLastRequest()->getBody(), true);

        $this->assertSame('noreply@carpay.com', $sent['from_email']);
        $this->assertSame('Carpay', $sent['from_name']);
        $this->assertSame('support@carpay.com', $sent['reply_to_email']);
        $this->assertSame('invoice.pdf', $sent['attachments'][0]['filename']);
    }

    /**
     * Test that sendBatch posts a messages envelope and returns per-item results.
     *
     * @return void
     */
    public function testSendBatchReturnsResults(): void
    {
        $responseBody = [
            'accepted' => 1,
            'rejected' => 1,
            'results'  => [
                ['index' => 0, 'status' => 'queued'],
                ['index' => 1, 'status' => 'rejected', 'error' => 'Campaign [x] not found or inactive'],
            ],
        ];

        $mock     = new MockHandler([
            new Response(207, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $result = $resource->sendBatch([
            ['campaign_key' => 'welcome', 'template_key' => 'welcome_email_en', 'recipient_address' => 'a@example.com'],
            ['campaign_key' => 'x', 'template_key' => 'welcome_email_en', 'recipient_address' => 'b@example.com'],
        ]);

        $this->assertSame(1, $result['accepted']);
        $this->assertSame(1, $result['rejected']);
        $this->assertSame('queued', $result['results'][0]['status']);

        $request = $mock->getLastRequest();
        $this->assertSame('/api/v1/send/batch', $request->getUri()->getPath());
        $sent = json_decode((string) $request->getBody(), true);
        $this->assertCount(2, $sent['messages']);
    }

    /**
     * Test that queueBatch posts to the queue batch endpoint.
     *
     * @return void
     */
    public function testQueueBatchReturnsResults(): void
    {
        $responseBody = [
            'accepted' => 1,
            'rejected' => 0,
            'results'  => [['index' => 0, 'status' => 'queued', 'id' => 5]],
        ];

        $mock     = new MockHandler([
            new Response(207, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $result = $resource->queueBatch([
            [
                'campaign_key'      => 'welcome',
                'template_key'      => 'welcome_email_en',
                'recipient_address' => 'a@example.com',
                'scheduled_at'      => '2099-01-01T00:00:00Z',
            ],
        ]);

        $this->assertSame(1, $result['accepted']);
        $this->assertSame(5, $result['results'][0]['id']);
        $this->assertSame('/api/v1/queue/batch', $mock->getLastRequest()->getUri()->getPath());
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
            $resource->send('', 'email_en', '');
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
     * Test that listing email subscriptions returns paginated data.
     *
     * @return void
     */
    public function testListEmailSubscriptionsReturnsPaginatedData(): void
    {
        $responseBody = [
            'current_page' => 1,
            'data'         => [
                ['id' => 1, 'email' => 'user@example.com', 'categories' => ['promotional']],
            ],
            'total'    => 1,
            'per_page' => 25,
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createEmailSubscriptionsResource($mock);

        $result = $resource->list(['per_page' => 10]);

        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertSame('user@example.com', $result['data'][0]['email']);
    }

    /**
     * Test that checking email subscription returns status.
     *
     * @return void
     */
    public function testCheckEmailSubscriptionReturnsStatus(): void
    {
        $responseBody = [
            'email'                => 'user@example.com',
            'opted_out_categories' => ['promotional'],
            'is_fully_opted_out'   => false,
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createEmailSubscriptionsResource($mock);

        $result = $resource->check('user@example.com');

        $this->assertSame('user@example.com', $result['email']);
        $this->assertSame(['promotional'], $result['opted_out_categories']);
        $this->assertFalse($result['is_fully_opted_out']);
    }

    /**
     * Create an EmailSubscriptions resource with a mocked Guzzle client.
     *
     * @param MockHandler $mock The mock handler pre-loaded with responses
     *
     * @return EmailSubscriptions
     */
    private function createEmailSubscriptionsResource(MockHandler $mock): EmailSubscriptions
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

        return new EmailSubscriptions($client);
    }

    /**
     * Test that the static factory creates a functional client instance.
     *
     * @return void
     */
    public function testStaticFactoryCreatesClient(): void
    {
        $client = YolioClient::create('https://hub.example.com', 'test-key');

        $this->assertInstanceOf(YolioClient::class, $client);
        $this->assertInstanceOf(CampaignResource::class, $client->campaigns());
        $this->assertInstanceOf(TemplateResource::class, $client->templates());
        $this->assertInstanceOf(MessageResource::class, $client->messages());
        $this->assertInstanceOf(MessageLogResource::class, $client->messageLog());
        $this->assertInstanceOf(EmailSubscriptions::class, $client->emailSubscriptions());
    }
}
