# messaging-hub/sdk

PHP SDK for the Messaging Hub API. Provides a fluent, resource-based client for sending messages, managing campaigns, templates, and partials, querying message history, and checking email subscription status.

## Requirements

- PHP >= 7.4
- Guzzle ^7.0

## Installation

```bash
composer require messaging-hub/sdk
```

## Quick Start

```php
use MessagingHub\Sdk\MessagingHubClient;

$client = MessagingHubClient::create(
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
$client = MessagingHubClient::create(
    'https://hub.example.com/api/v1',
    'your-api-key'
);
```

### Custom Timeout

```php
use MessagingHub\Sdk\ClientConfig;
use MessagingHub\Sdk\MessagingHubClient;

$config = new ClientConfig(
    'https://hub.example.com/api/v1',
    'your-api-key',
    60 // timeout in seconds (default: 30)
);
$client = new MessagingHubClient($config);
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

### Messages

#### `send(campaignKey, templateKey, recipientAddress, options): array`

Send a message immediately. The Hub processes it asynchronously and returns 202 Accepted. The channel (email, SMS, etc.) is determined from the resolved template.

```php
$response = $client->messages()->send(
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
// $response: ['message' => 'Message dispatched.']
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

#### `queue(campaignKey, templateKey, recipientAddress, scheduledAt, options): array`

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
// $response: ['message' => 'Message queued.', 'id' => 42]
```

Accepts the same options as `send()`.

---

### Campaigns

#### `list(params): array`

List all campaigns for the authenticated tenant.

```php
$campaigns = $client->campaigns()->list(['active' => true, 'per_page' => 25]);
```

#### `get(id): array`

Get a single campaign by ID, including its templates.

```php
$campaign = $client->campaigns()->get(1);
```

#### `create(data): array`

Create a new campaign.

```php
$campaign = $client->campaigns()->create([
    'key'          => 'order-shipped',
    'name'         => 'Order Shipped Notification',
    'channels'     => ['email', 'sms'],
    'trigger_type' => 'event',
    'category'     => 'operational',
]);
```

#### `update(id, data): array`

Update an existing campaign. Partial updates are supported.

```php
$campaign = $client->campaigns()->update(1, ['is_active' => false]);
```

#### `delete(id): void`

Delete a campaign.

```php
$client->campaigns()->delete(1);
```

---

### Templates

Templates are scoped to campaigns and identified by a unique `key` within the campaign.

#### `listForCampaign(campaignId, params): array`

List all templates for a campaign.

```php
$templates = $client->templates()->listForCampaign(1, ['channel' => 'email']);
```

**Query parameters:** `channel` (email, sms, push, call), `locale` (en, es).

#### `get(id): array`

Get a single template by ID.

```php
$template = $client->templates()->get(5);
```

#### `create(campaignId, data): array`

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

#### `update(id, data): array`

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

#### `list(params): array`

List all template partials for the authenticated tenant.

```php
$partials = $client->partials()->list(['per_page' => 25]);
```

#### `get(id): array`

Get a single partial by ID.

```php
$partial = $client->partials()->get(3);
```

#### `create(data): array`

Create a new template partial.

```php
$partial = $client->partials()->create([
    'key'         => 'email-footer',
    'body'        => '<footer>Copyright 2026 Acme Inc.</footer>',
    'description' => 'Standard email footer',
    'is_active'   => true,
]);
```

#### `update(id, data): array`

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

#### `list(params): array`

List messages from the message log with optional filters.

```php
$messages = $client->messageLog()->list([
    'campaign_id' => 1,
    'channel'     => 'email',
    'status'      => 'delivered',
    'per_page'    => 50,
]);
```

**Query parameters:** `campaign_id`, `channel`, `status`, `recipient_address`, `per_page`.

#### `recipientHistory(ref, params): array`

Get all messages sent to a recipient by their unique reference.

```php
$history = $client->messageLog()->recipientHistory('user-123', ['per_page' => 10]);
```

---

### Email Subscriptions

Read-only access to email opt-out / unsubscribe preferences.

#### `list(params): array`

List all email unsubscriptions for the authenticated tenant.

```php
$unsubscribes = $client->emailSubscriptions()->list(['per_page' => 25]);
```

#### `check(email): array`

Check the opt-out status of a specific email address.

```php
$status = $client->emailSubscriptions()->check('user@example.com');
// $status: [
//     'email' => 'user@example.com',
//     'opted_out_categories' => ['promotional'],
//     'is_fully_opted_out' => false,
// ]
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

```php
use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

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

The PHP SDK uses **snake_case** for all request and response data, matching the API wire format directly. No automatic case conversion is performed.

## Development

```bash
composer install
vendor/bin/phpunit
```

When running inside the Messaging Hub Docker stack:

```bash
docker compose exec -T -w /var/www/html/sdks/php app vendor/bin/phpunit
```
