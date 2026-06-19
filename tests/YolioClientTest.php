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
                'id'                => 42,
                'message_id'        => '01JZ8K9F3QG7N2R5T8V0W1X2Y3',
                'tenant_id'         => 1,
                'channel'           => 'email',
                'recipient_address' => 'customer@example.com',
                'status'            => 'delivered',
                'sent_at'           => '2026-03-15T10:30:05.000000Z',
                'created_at'        => '2026-03-15T10:30:00.000000Z',
                'updated_at'        => '2026-03-15T10:30:08.000000Z',
            ])),
        ]);
        $resource = $this->createMessageLogResource($mock);

        $result = $resource->find('01JZ8K9F3QG7N2R5T8V0W1X2Y3');

        $this->assertInstanceOf(\Yolio\Sdk\Dto\MessageLog::class, $result);
        $this->assertSame('01JZ8K9F3QG7N2R5T8V0W1X2Y3', $result->message_id);
        $this->assertSame('delivered', $result->status);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->sent_at);
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
            'message'    => 'Message queued for delivery',
            'message_id' => '01JZ8K9F3QG7N2R5T8V0W1X2Y3',
        ];

        $mock    = new MockHandler([
            new Response(202, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $result = $resource->send('welcome-email', 'welcome_email_en', 'user@example.com', [
            'data' => ['name' => 'John'],
        ]);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\SendResult::class, $result);
        $this->assertSame('Message queued for delivery', $result->message);
        $this->assertSame('01JZ8K9F3QG7N2R5T8V0W1X2Y3', $result->message_id);
    }

    /**
     * Test that queuing a message returns a response with an ID.
     *
     * @return void
     */
    public function testSuccessfulQueueReturnsResponseWithId(): void
    {
        $responseBody = [
            'message'    => 'Message scheduled for delivery',
            'id'         => 99,
            'message_id' => '01JZ8K9F3QG7N2R5T8V0W1X2Y3',
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

        $this->assertInstanceOf(\Yolio\Sdk\Dto\QueueResult::class, $result);
        $this->assertSame(99, $result->id);
        $this->assertSame('01JZ8K9F3QG7N2R5T8V0W1X2Y3', $result->message_id);
        $this->assertSame('Message scheduled for delivery', $result->message);
    }

    /**
     * Test that send forwards email sender options and attachments in the payload.
     *
     * @return void
     */
    public function testSendForwardsEmailOptionsInPayload(): void
    {
        $mock     = new MockHandler([
            new Response(202, [], json_encode(['message' => 'queued', 'message_id' => '01JZ8K9F3QG7N2R5T8V0W1X2Y3'])),
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

        $this->assertInstanceOf(\Yolio\Sdk\Dto\BatchResult::class, $result);
        $this->assertSame(1, $result->accepted);
        $this->assertSame(1, $result->rejected);
        $this->assertSame('queued', $result->results[0]->status);
        $this->assertTrue($result->results[0]->isQueued());
        $this->assertSame('Campaign [x] not found or inactive', $result->results[1]->error);

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

        $this->assertSame(1, $result->accepted);
        $this->assertSame(5, $result->results[0]->id);
        $this->assertSame('/api/v1/queue/batch', $mock->getLastRequest()->getUri()->getPath());
    }

    /**
     * Test that listing campaigns returns paginated data.
     *
     * @return void
     */
    public function testListCampaignsReturnsPaginatedData(): void
    {
        $campaign = static function (int $id, string $key, string $name): array {
            return [
                'id'           => $id,
                'tenant_id'    => 1,
                'key'          => $key,
                'name'         => $name,
                'description'  => null,
                'channels'     => ['email'],
                'trigger_type' => 'event',
                'category'     => 'operational',
                'is_active'    => true,
                'settings'     => null,
                'created_at'   => '2026-01-15T10:30:00.000000Z',
                'updated_at'   => '2026-01-15T10:30:00.000000Z',
            ];
        };

        $responseBody = [
            'current_page' => 1,
            'data'         => [
                $campaign(1, 'welcome-email', 'Welcome Email'),
                $campaign(2, 'payment-reminder', 'Payment Reminder'),
            ],
            'per_page' => 15,
            'total'    => 2,
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $result = $resource->list(['active' => true]);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\Paginated::class, $result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(\Yolio\Sdk\Dto\Campaign::class, $result->data[0]);
        $this->assertSame('welcome-email', $result->data[0]->key);
        $this->assertSame(1, $result->current_page);

        // The collection is iterable over its items.
        $keys = [];
        foreach ($result as $campaign) {
            $keys[] = $campaign->key;
        }
        $this->assertSame(['welcome-email', 'payment-reminder'], $keys);
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
            'tenant_id'    => 1,
            'key'          => 'order-confirmation',
            'name'         => 'Order Confirmation',
            'description'  => null,
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email', 'sms'],
            'is_active'    => true,
            'settings'     => null,
            'created_at'   => '2026-01-15T10:30:00.000000Z',
            'updated_at'   => '2026-01-15T10:30:00.000000Z',
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

        $this->assertInstanceOf(\Yolio\Sdk\Dto\Campaign::class, $result);
        $this->assertSame(3, $result->id);
        $this->assertSame('order-confirmation', $result->key);
        $this->assertSame('Order Confirmation', $result->name);
        $this->assertSame(['email', 'sms'], $result->channels);
        $this->assertTrue($result->is_active);
    }

    /**
     * Test that getting a campaign returns the campaign with its templates.
     *
     * @return void
     */
    public function testGetCampaignReturnsCampaignWithTemplates(): void
    {
        $responseBody = [
            'id'           => 1,
            'tenant_id'    => 1,
            'key'          => 'welcome-email',
            'name'         => 'Welcome Email',
            'description'  => null,
            'channels'     => ['email'],
            'trigger_type' => 'event',
            'category'     => 'operational',
            'is_active'    => true,
            'settings'     => null,
            'created_at'   => '2026-01-15T10:30:00.000000Z',
            'updated_at'   => '2026-01-15T10:30:00.000000Z',
            'templates'    => [
                ['id' => 10, 'channel' => 'email', 'locale' => 'en'],
                ['id' => 11, 'channel' => 'email', 'locale' => 'es'],
            ],
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $result = $resource->get(1);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\Campaign::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('welcome-email', $result->key);
        $this->assertCount(2, $result->templates);
        $this->assertInstanceOf(\Yolio\Sdk\Dto\TemplateSummary::class, $result->templates[0]);
        $this->assertSame('en', $result->templates[0]->locale);
        $this->assertSame('es', $result->templates[1]->locale);
    }

    /**
     * Test that DTOs cast timestamps to DateTimeImmutable and expose a raw-array escape hatch.
     *
     * @return void
     */
    public function testCampaignDtoCastsDatesAndSerializes(): void
    {
        $responseBody = [
            'id'           => 1,
            'tenant_id'    => 7,
            'key'          => 'welcome-email',
            'name'         => 'Welcome Email',
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email'],
            'is_active'    => true,
            'created_at'   => '2026-01-15T10:30:00.000000Z',
            'updated_at'   => '2026-01-16T08:00:00.000000Z',
        ];

        $mock     = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $campaign = $resource->get(1);

        // Timestamps are hydrated as DateTimeImmutable.
        $this->assertInstanceOf(\DateTimeImmutable::class, $campaign->created_at);
        $this->assertSame('2026-01-15', $campaign->created_at->format('Y-m-d'));

        // toArray() and json serialization provide a raw-data escape hatch (snake_case keys),
        // with DateTimeImmutable values serialized back to ISO 8601 strings.
        $array = $campaign->toArray();
        $this->assertSame('welcome-email', $array['key']);
        $this->assertSame(7, $array['tenant_id']);
        $this->assertTrue($array['is_active']);
        $this->assertIsString($array['created_at']);
        $this->assertStringStartsWith('2026-01-15', $array['created_at']);

        $decoded = json_decode(json_encode($campaign), true);
        $this->assertSame('welcome-email', $decoded['key']);
    }

    /**
     * Test that the DTO base enforces required and typed properties.
     *
     * @return void
     */
    public function testDtoEnforcesRequiredAndTypedProperties(): void
    {
        // Missing a required (non-nullable) property.
        try {
            \Yolio\Sdk\Dto\SendResult::make(['message' => 'ok']); // message_id missing
            $this->fail('Expected DataTransferObjectException for missing required property.');
        } catch (\Yolio\Sdk\Dto\DataTransferObjectException $e) {
            $this->assertStringContainsString('message_id', $e->getMessage());
        }

        // Wrong scalar type for a declared property.
        try {
            \Yolio\Sdk\Dto\SendResult::make(['message' => 123, 'message_id' => 'abc']);
            $this->fail('Expected DataTransferObjectException for invalid property type.');
        } catch (\Yolio\Sdk\Dto\DataTransferObjectException $e) {
            $this->assertStringContainsString('message', $e->getMessage());
        }

        // Accessing an undeclared property.
        $result = \Yolio\Sdk\Dto\SendResult::make(['message' => 'ok', 'message_id' => 'abc']);
        try {
            $result->nonexistent;
            $this->fail('Expected DataTransferObjectException for undeclared property access.');
        } catch (\Yolio\Sdk\Dto\DataTransferObjectException $e) {
            $this->assertStringContainsString('nonexistent', $e->getMessage());
        }
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
                [
                    'id'         => 1,
                    'email'      => 'user@example.com',
                    'categories' => ['promotional'],
                    'created_at' => '2026-01-15T10:30:00.000000Z',
                    'updated_at' => '2026-01-15T10:30:00.000000Z',
                ],
            ],
            'total'    => 1,
            'per_page' => 25,
        ];

        $mock    = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createEmailSubscriptionsResource($mock);

        $result = $resource->list(['per_page' => 10]);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\Paginated::class, $result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(\Yolio\Sdk\Dto\EmailSubscription::class, $result->data[0]);
        $this->assertSame('user@example.com', $result->data[0]->email);
        $this->assertSame(['promotional'], $result->data[0]->categories);
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

        $this->assertInstanceOf(\Yolio\Sdk\Dto\EmailSubscriptionStatus::class, $result);
        $this->assertSame('user@example.com', $result->email);
        $this->assertSame(['promotional'], $result->opted_out_categories);
        $this->assertFalse($result->is_fully_opted_out);
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
     * Test that create() accepts a CampaignRequest input DTO.
     *
     * @return void
     */
    public function testCreateAcceptsRequestObject(): void
    {
        $responseBody = [
            'id'           => 3,
            'tenant_id'    => 1,
            'key'          => 'order-confirmation',
            'name'         => 'Order Confirmation',
            'description'  => null,
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email'],
            'is_active'    => true,
            'settings'     => null,
            'created_at'   => '2026-01-15T10:30:00.000000Z',
            'updated_at'   => '2026-01-15T10:30:00.000000Z',
        ];

        $mock     = new MockHandler([
            new Response(201, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        $request = \Yolio\Sdk\Dto\CampaignRequest::make([
            'key'          => 'order-confirmation',
            'name'         => 'Order Confirmation',
            'channels'     => ['email'],
            'trigger_type' => 'event',
            'category'     => 'operational',
        ]);

        $result = $resource->create($request);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\Campaign::class, $result);

        // The request body carries exactly the fields that were set (snake_case).
        $sent = json_decode((string) $mock->getLastRequest()->getBody(), true);
        $this->assertSame(
            ['key', 'name', 'channels', 'trigger_type', 'category'],
            array_keys($sent)
        );
        $this->assertSame('order-confirmation', $sent['key']);
    }

    /**
     * Test that a request DTO sends only the fields explicitly set, including explicit null.
     *
     * @return void
     */
    public function testRequestObjectSendsOnlySetFieldsIncludingNull(): void
    {
        $responseBody = [
            'id'           => 1,
            'tenant_id'    => 1,
            'key'          => 'welcome',
            'name'         => 'Welcome',
            'description'  => null,
            'trigger_type' => 'event',
            'category'     => 'operational',
            'channels'     => ['email'],
            'is_active'    => false,
            'settings'     => null,
            'created_at'   => '2026-01-15T10:30:00.000000Z',
            'updated_at'   => '2026-01-15T10:30:00.000000Z',
        ];

        $mock     = new MockHandler([
            new Response(200, [], json_encode($responseBody)),
        ]);
        $resource = $this->createCampaignResource($mock);

        // Partial update: clear description (explicit null) and deactivate — nothing else.
        $request = \Yolio\Sdk\Dto\CampaignRequest::make();
        $request->description = null;
        $request->is_active   = false;

        $resource->update(1, $request);

        $sent = json_decode((string) $mock->getLastRequest()->getBody(), true);
        $this->assertSame(['description', 'is_active'], array_keys($sent));
        $this->assertNull($sent['description']);
        $this->assertFalse($sent['is_active']);
    }

    /**
     * Test that send() accepts a MessageRequest input DTO.
     *
     * @return void
     */
    public function testSendAcceptsMessageRequest(): void
    {
        $mock     = new MockHandler([
            new Response(202, [], json_encode(['message' => 'queued', 'message_id' => '01JZ8K9F3QG7N2R5T8V0W1X2Y3'])),
        ]);
        $resource = $this->createMessageResource($mock);

        $request = \Yolio\Sdk\Dto\MessageRequest::make([
            'campaign_key'      => 'welcome',
            'template_key'      => 'welcome_email_en',
            'recipient_address' => 'user@example.com',
            'from_email'        => 'billing@carpay.com',
            'data'              => ['name' => 'Jane'],
        ]);

        $result = $resource->send($request);

        $this->assertInstanceOf(\Yolio\Sdk\Dto\SendResult::class, $result);

        $sent = json_decode((string) $mock->getLastRequest()->getBody(), true);
        $this->assertSame('welcome', $sent['campaign_key']);
        $this->assertSame('billing@carpay.com', $sent['from_email']);
        $this->assertSame(['name' => 'Jane'], $sent['data']);
        $this->assertArrayNotHasKey('scheduled_at', $sent);
    }

    /**
     * Test that sendBatch() accepts MessageRequest items mixed with arrays.
     *
     * @return void
     */
    public function testSendBatchAcceptsMessageRequestItems(): void
    {
        $responseBody = [
            'accepted' => 2,
            'rejected' => 0,
            'results'  => [
                ['index' => 0, 'status' => 'queued', 'message_id' => '01A'],
                ['index' => 1, 'status' => 'queued', 'message_id' => '01B'],
            ],
        ];

        $mock     = new MockHandler([
            new Response(207, [], json_encode($responseBody)),
        ]);
        $resource = $this->createMessageResource($mock);

        $resource->sendBatch([
            \Yolio\Sdk\Dto\MessageRequest::make([
                'campaign_key'      => 'welcome',
                'template_key'      => 'welcome_email_en',
                'recipient_address' => 'a@example.com',
            ]),
            ['campaign_key' => 'welcome', 'template_key' => 'welcome_email_en', 'recipient_address' => 'b@example.com'],
        ]);

        $sent = json_decode((string) $mock->getLastRequest()->getBody(), true);
        $this->assertCount(2, $sent['messages']);
        $this->assertSame('a@example.com', $sent['messages'][0]['recipient_address']);
        $this->assertSame('b@example.com', $sent['messages'][1]['recipient_address']);
    }

    /**
     * Test that request DTOs reject unknown and mistyped fields.
     *
     * @return void
     */
    public function testRequestObjectRejectsUnknownAndMistypedFields(): void
    {
        try {
            \Yolio\Sdk\Dto\CampaignRequest::make(['nope' => 'x']);
            $this->fail('Expected exception for unknown request field.');
        } catch (\Yolio\Sdk\Dto\DataTransferObjectException $e) {
            $this->assertStringContainsString('nope', $e->getMessage());
        }

        try {
            \Yolio\Sdk\Dto\CampaignRequest::make(['channels' => 'email']); // expects array
            $this->fail('Expected exception for mistyped request field.');
        } catch (\Yolio\Sdk\Dto\DataTransferObjectException $e) {
            $this->assertStringContainsString('channels', $e->getMessage());
        }
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
