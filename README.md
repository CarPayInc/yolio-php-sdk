# yolio/sdk

PHP SDK for the Yolio API. Provides a fluent, resource-based client for sending messages, managing campaigns, templates, and partials, querying message history, and checking email subscription status.

## Requirements

- PHP >= 7.4
- Guzzle ^7.0

## Installation

```bash
composer require yolio/sdk
```

## Quick Start

```php
use Yolio\Sdk\YolioClient;

$client = YolioClient::create(
    'https://hub.example.com/api/v1',
    'your-api-key'
);

// Send an email immediately
$client->messages()->send(
    'welcome-email',        // campaign key
    'welcome_email_en',     // template key
    'customer@example.com', // recipient address
    ['data' => ['name' => 'Jane Doe']]
);
```

## Configuration

### Static Factory (Recommended)

```php
$client = YolioClient::create(
    'https://hub.example.com/api/v1',
    'your-api-key'
);
```

### Custom Timeout

```php
use Yolio\Sdk\ClientConfig;
use Yolio\Sdk\YolioClient;

$config = new ClientConfig(
    'https://hub.example.com/api/v1',
    'your-api-key',
    60 // timeout in seconds (default: 30)
);
$client = new YolioClient($config);
```

## Resources

The client exposes six resource groups:

```php
$client->messages();            // Send and queue messages
$client->campaigns();           // Campaign CRUD
$client->templates();           // Template CRUD (scoped to campaigns)
$client->partials();            // Template partial CRUD
$client->messageLog();          // Message history queries
$client->emailSubscriptions();  // Email opt-out status
```

---

## Data Objects

Methods return **hydrated, typed data objects** (DTOs) under the `Yolio\Sdk\Dto`
namespace rather than raw associative arrays. Properties are named to match the
API wire format (`snake_case`) and are read directly; timestamps are cast to
`DateTimeImmutable`:

```php
$campaign = $client->campaigns()->get(1);

$campaign->key;                  // string
$campaign->is_active;            // bool
$campaign->created_at;           // DateTimeImmutable
$campaign->templates[0]->locale; // nested TemplateSummary
```

The entity DTOs are `Campaign`, `Template`, `MessageLog`, `EmailSubscription`,
`EmailSubscriptionStatus`, and `TemplatePartial`. Send/queue return `SendResult`
and `QueueResult`; batch calls return `BatchResult` (with `BatchItemResult[]`).

**Paginated results** are returned as a `Paginated` collection that is iterable
and countable, with the page items in `->data` and the pagination metadata as
typed properties (`->current_page`, `->total`, `->per_page`, `->last_page`, …):

```php
$page = $client->campaigns()->list(['active' => true]);

foreach ($page as $campaign) {   // iterate the current page
    echo $campaign->name;
}
count($page);                    // items on this page
$page->total;                    // total across all pages
```

**Escape hatch:** every DTO implements `JsonSerializable` and exposes
`toArray()`, returning the wire-format (snake_case) data — useful if you prefer
raw arrays or want to re-encode the response:

```php
$raw = $client->campaigns()->get(1)->toArray();   // ['id' => 1, 'key' => ..., ...]
$json = json_encode($client->messages()->send(...));
```

### Extending

DTOs derive hydration and serialization from their declared properties via
reflection — there is no per-property mapping code. To add or change a field,
update the typed `protected` property (and its `@property-read` annotation) on
the DTO; `make()` and `toArray()` follow automatically. A `casts()` method
declares richer types: `DateTimeImmutable::class` for timestamps, a DTO class
(or `[DtoClass]` for a list) to hydrate nested objects.

## Request Objects (input)

The SDK is symmetrical: alongside the output DTOs, every write method also
accepts a typed **request object** — `CampaignRequest`, `TemplateRequest`,
`PartialRequest`, and `MessageRequest`. You can always pass a plain snake_case
array instead; the request objects are an optional, type-checked convenience.

Build one with `make()` or by assigning properties (both equivalent):

```php
use Yolio\Sdk\Dto\CampaignRequest;

$request = CampaignRequest::make([
    'key'          => 'order-shipped',
    'name'         => 'Order Shipped',
    'channels'     => ['email', 'sms'],
    'trigger_type' => 'event',
    'category'     => 'operational',
]);

// or, fluently:
$request = CampaignRequest::make();
$request->key  = 'order-shipped';
$request->name = 'Order Shipped';
```

Key behaviors:

- **Only the fields you set are sent.** Unset fields are omitted from the
  payload entirely — so the same object type works for both create and partial
  update. (The API enforces which fields are required and returns `422` if one
  is missing.)
- **Explicit `null` is preserved**, so you can intentionally clear a nullable
  field on update (e.g. `$request->description = null;`) — distinct from leaving
  it unset.
- **Unknown or mistyped fields throw** immediately (a `DataTransferObjectException`),
  catching typos at the call site.

---

### Messages

#### `send(campaignKey, templateKey, recipientAddress, options): SendResult`

Send a message immediately. The Hub processes it asynchronously and returns 202 Accepted. The channel (email, SMS, etc.) is determined from the resolved template.

Call it positionally with an options array (input), and read the typed result (output):

```php
$result = $client->messages()->send(
    'welcome-email',
    'welcome_email_en',
    'customer@example.com',
    [
        'recipient_name' => 'Jane Doe',
        'recipient_ref'  => 'user-123',
        'data'           => ['name' => 'Jane'],
        'metadata'       => ['source' => 'signup'],
    ]
);

$result->message_id;   // '01JZ...'  (SendResult)
```

Or pass a typed `MessageRequest` — `send()` accepts either form:

```php
use Yolio\Sdk\Dto\MessageRequest;

$message = MessageRequest::make([
    'campaign_key'      => 'welcome-email',
    'template_key'      => 'welcome_email_en',
    'recipient_address' => 'customer@example.com',
    'recipient_name'    => 'Jane Doe',
    'data'              => ['name' => 'Jane'],
]);

$result = $client->messages()->send($message);
```

For SMS with multi-number tenants, pass the `from` option to specify the sender phone number or label:

```php
$response = $client->messages()->send(
    'payment-reminder',
    'payment_reminder_sms_en',
    '+1234567890',
    [
        'from' => '+19876543210', // E.164 number or label (e.g. 'billing')
        'data' => ['amount' => '150.00'],
    ]
);
```

**Options:**

| Key | Type | Description |
|-----|------|-------------|
| `recipient_name` | string | Display name of the recipient |
| `recipient_ref` | string | Unique reference for the recipient (e.g. user ID) |
| `data` | array | Template placeholder values |
| `metadata` | array | Arbitrary metadata stored with the message log |
| `from` | string | SMS sender phone number (E.164) or label. Required when tenant has multiple active phone numbers. |
| `from_email` | string | Email channel: per-message sender address. Must use an approved sender domain, else `422`. |
| `from_name` | string | Email channel: per-message sender display name. |
| `reply_to_email` | string | Email channel: per-message reply-to address. |
| `reply_to_name` | string | Email channel: per-message reply-to display name. |
| `attachments` | array | Email channel: list of `['filename' => ..., 'content' => base64, 'content_type' => ...]`. |

**Email with a custom sender, reply-to, and attachment:**

```php
$client->messages()->send(
    'statement-ready',
    'statement_email_en',
    'customer@example.com',
    [
        'from_email'     => 'billing@carpay.com',   // domain must be approved for the tenant
        'from_name'      => 'Carpay Billing',
        'reply_to_email' => 'support@carpay.com',
        'attachments'    => [
            [
                'filename'     => 'statement.pdf',
                'content'      => base64_encode($pdfBytes),
                'content_type' => 'application/pdf',
            ],
        ],
    ]
);
```

#### `queue(campaignKey, templateKey, recipientAddress, scheduledAt, options): QueueResult`

Schedule a message for future delivery.

```php
$response = $client->messages()->queue(
    'payment-reminder',
    'payment_reminder_sms_en',
    '+1234567890',
    '2026-03-15T10:00:00Z', // ISO 8601
    [
        'data' => ['amount' => '150.00'],
    ]
);
// $response: QueueResult { message: 'Message scheduled for delivery', id: 42, message_id: '01JZ...' }
```

Accepts the same options as `send()`.

#### `sendBatch(messages): BatchResult`

Send up to 100 messages in a single request. Each item is either a flat array (with the same keys as `send()`) or a `MessageRequest` — you can mix both. Items are validated and dispatched independently; the result reports each item's outcome.

```php
use Yolio\Sdk\Dto\MessageRequest;

$result = $client->messages()->sendBatch([
    MessageRequest::make([
        'campaign_key'      => 'statement-ready',
        'template_key'      => 'statement_email_en',
        'recipient_address' => 'a@example.com',
    ]),
    ['campaign_key' => 'statement-ready', 'template_key' => 'statement_email_en', 'recipient_address' => 'b@example.com', 'from_email' => 'billing@carpay.com'],
]);

$result->accepted;                  // 2
foreach ($result->results as $item) {
    $item->index;                   // position in the request
    $item->isQueued();              // bool
    $item->message_id;              // ULID for accepted items
    $item->error;                   // reason for rejected items
}
```

A rejected item (validation failure, opt-out, or `rate_limit_exceeded`) does not stop the rest of the batch. Envelope-level failures — more than 100 items, or the combined attachment size exceeding the per-batch budget — throw a `ValidationException`.

#### `queueBatch(messages): BatchResult`

Schedule up to 100 messages in a single request. Each item is a flat payload with the same keys as `queue()`, including a per-item `scheduled_at`. Accepted items include the queue entry `id`.

```php
$result = $client->messages()->queueBatch([
    [
        'campaign_key'      => 'statement-ready',
        'template_key'      => 'statement_email_en',
        'recipient_address' => 'a@example.com',
        'scheduled_at'      => '2026-04-01T09:00:00Z',
    ],
]);
```

---

### Campaigns

#### `list(params): Paginated`

List all campaigns for the authenticated tenant. Returns a `Paginated` collection of `Campaign`.

```php
$campaigns = $client->campaigns()->list(['active' => true, 'per_page' => 25]);
```

#### `get(id): Campaign`

Get a single campaign by ID, including its templates.

```php
$campaign = $client->campaigns()->get(1);
```

#### `create(data): Campaign`

Create a new campaign. Accepts an array or a `CampaignRequest`; returns the created `Campaign`.

```php
use Yolio\Sdk\Dto\CampaignRequest;

// Array form:
$campaign = $client->campaigns()->create([
    'key'          => 'order-shipped',
    'name'         => 'Order Shipped Notification',
    'channels'     => ['email', 'sms'],
    'trigger_type' => 'event',
    'category'     => 'operational',
]);

// Typed-request form (equivalent):
$campaign = $client->campaigns()->create(CampaignRequest::make([
    'key'          => 'order-shipped',
    'name'         => 'Order Shipped Notification',
    'channels'     => ['email', 'sms'],
    'trigger_type' => 'event',
    'category'     => 'operational',
]));

$campaign->id;          // int       (Campaign)
$campaign->created_at;  // DateTimeImmutable
```

#### `update(id, data): Campaign`

Update an existing campaign. Partial updates are supported — with a `CampaignRequest`, only the fields you set are sent (and an explicit `null` clears a nullable field).

```php
$campaign = $client->campaigns()->update(1, ['is_active' => false]);

// Typed-request form, clearing the description:
use Yolio\Sdk\Dto\CampaignRequest;

$request = CampaignRequest::make();
$request->is_active   = false;
$request->description = null;   // explicitly cleared
$campaign = $client->campaigns()->update(1, $request);
```

#### `delete(id): void`

Delete a campaign.

```php
$client->campaigns()->delete(1);
```

---

### Templates

Templates are scoped to campaigns and identified by a unique `key` within the campaign.

#### `listForCampaign(campaignId, params): Paginated`

List all templates for a campaign. Returns a `Paginated` collection of `Template`.

```php
$templates = $client->templates()->listForCampaign(1, ['channel' => 'email']);
```

**Query parameters:** `channel` (email, sms, push, call), `locale` (en, es).

#### `get(id): Template`

Get a single template by ID.

```php
$template = $client->templates()->get(5);
```

#### `create(campaignId, data): Template`

Create a new template under a campaign.

```php
$template = $client->templates()->create(1, [
    'key'       => 'order_shipped_email_en',
    'channel'   => 'email',
    'locale'    => 'en',
    'subject'   => 'Your order has shipped!',
    'body'      => '<h1>Hi [[name]]</h1><p>Your order #[[order_id]] is on its way.</p>',
    'is_active' => true,
]);
```

#### `update(id, data): Template`

Update an existing template. Partial updates are supported.

```php
$template = $client->templates()->update(5, ['subject' => 'Updated subject line']);
```

#### `delete(id): void`

Delete a template.

```php
$client->templates()->delete(5);
```

---

### Partials

Template partials are reusable content fragments (headers, footers, signature blocks) that can be included in templates.

#### `list(params): Paginated`

List all template partials for the authenticated tenant. Returns a `Paginated` collection of `TemplatePartial`.

```php
$partials = $client->partials()->list(['per_page' => 25]);
```

#### `get(id): TemplatePartial`

Get a single partial by ID.

```php
$partial = $client->partials()->get(3);
```

#### `create(data): TemplatePartial`

Create a new template partial.

```php
$partial = $client->partials()->create([
    'key'         => 'email-footer',
    'body'        => '<footer>Copyright 2026 Acme Inc.</footer>',
    'description' => 'Standard email footer',
    'is_active'   => true,
]);
```

#### `update(id, data): TemplatePartial`

Update an existing partial. Partial updates are supported.

```php
$partial = $client->partials()->update(3, ['body' => '<footer>Updated footer</footer>']);
```

#### `delete(id): void`

Delete a template partial.

```php
$client->partials()->delete(3);
```

---

### Message Log

Read-only access to the message log for querying sent message history.

#### `list(params): Paginated`

List messages from the message log with optional filters. Returns a `Paginated` collection of `MessageLog`.

```php
$messages = $client->messageLog()->list([
    'campaign_id' => 1,
    'channel'     => 'email',
    'status'      => 'delivered',
    'start_date'  => '2026-03-01',         // date or ISO 8601 datetime (tenant timezone)
    'end_date'    => '2026-03-31',         // bare dates are inclusive of the whole day
    'per_page'    => 50,
]);
```

**Query parameters:** `campaign_id`, `channel`, `status`, `recipient_address`, `start_date`, `end_date`, `per_page`.

The `start_date` / `end_date` window filters on the message `created_at` timestamp. Values accept a bare date (`YYYY-MM-DD`) or a full ISO 8601 datetime, resolved in the tenant's timezone; a bare `end_date` is inclusive of the entire day.

#### `recipientHistory(ref, params): Paginated`

Get all messages sent to a recipient by their unique reference. Returns a `Paginated` collection of `MessageLog`. Accepts the same `start_date` / `end_date` window parameters as `list()`.

```php
$history = $client->messageLog()->recipientHistory('user-123', [
    'start_date' => '2026-03-01',
    'per_page'   => 10,
]);
```

#### `find(messageId): MessageLog`

Look up a single message by the `message_id` (ULID) returned from `send()` / `queue()`. A dispatched message resolves to its delivery record; a still-scheduled message resolves to its queued representation (status `scheduled`/`processing`, delivery fields `null`).

```php
$message = $client->messageLog()->find('01JZ8K9F3QG7N2R5T8V0W1X2Y3');

$message->status;        // e.g. 'delivered' or 'scheduled'
$message->delivered_at;  // ?DateTimeImmutable
```

---

### Email Subscriptions

Read-only access to email opt-out / unsubscribe preferences.

#### `list(params): Paginated`

List all email unsubscriptions for the authenticated tenant. Returns a `Paginated` collection of `EmailSubscription`.

```php
$unsubscribes = $client->emailSubscriptions()->list(['per_page' => 25]);
```

#### `check(email): EmailSubscriptionStatus`

Check the opt-out status of a specific email address.

```php
$status = $client->emailSubscriptions()->check('user@example.com');
// $status: EmailSubscriptionStatus {
//     email: 'user@example.com',
//     opted_out_categories: ['promotional'],
//     is_fully_opted_out: false,
// }
```

---

## Error Handling

All methods throw typed exceptions on failure:

| Exception | HTTP Status | Methods |
|-----------|-------------|---------|
| `AuthenticationException` | 401 | `getMessage()`, `getStatusCode()` |
| `ValidationException` | 422 | `getMessage()`, `getStatusCode()`, `getErrors()` |
| `ApiException` | Other / network | `getMessage()`, `getStatusCode()` |

`getStatusCode()` returns `null` for network/connection errors (non-HTTP failures).

A tenant that exceeds its per-minute message budget receives `429 Too Many Requests`, surfaced as an `ApiException` with `getStatusCode() === 429`. Back off and retry (the API also returns a `Retry-After` header).

```php
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

try {
    $client->messages()->send('welcome-email', 'welcome_email_en', 'user@example.com');
} catch (ValidationException $e) {
    // $e->getErrors(): ['recipient_address' => ['The recipient address field is required.']]
    foreach ($e->getErrors() as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} catch (AuthenticationException $e) {
    echo "Invalid API key\n";
} catch (ApiException $e) {
    echo "API error ({$e->getStatusCode()}): {$e->getMessage()}\n";
}
```

## Data Format

Both **requests and responses** use **snake_case**, matching the API wire format directly. Request payloads are snake_case arrays; responses are hydrated into typed DTOs whose properties are also snake_case (see [Data Objects](#data-objects)). Call `toArray()` on any DTO to get the raw wire-format array back.

## Development

```bash
composer install
vendor/bin/phpunit
```

When running inside the Yolio Docker stack:

```bash
docker compose exec -T -w /var/www/html/sdks/php app vendor/bin/phpunit
```
