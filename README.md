# messaging-hub/sdk

PHP SDK for the Messaging Hub API. Provides a fluent, resource-based client for sending messages, managing campaigns and templates, and querying message history.

## Requirements

- PHP >= 7.4
- Guzzle ^7.0

## Installation

```bash
composer require messaging-hub/sdk
```

## Setup

```php
use MessagingHub\Sdk\ClientConfig;
use MessagingHub\Sdk\MessagingHubClient;

// Quick initialization
$client = MessagingHubClient::create(
    'https://hub.example.com/api/v1',
    'your-api-key'
);

// Or with custom timeout
$config = new ClientConfig(
    'https://hub.example.com/api/v1',
    'your-api-key',
    60 // timeout in seconds, defaults to 30
);
$client = new MessagingHubClient($config);
```

## Usage

The client exposes four resource groups, each accessible via a fluent method:

```php
$client->messages();   // send and queue messages
$client->campaigns();  // campaign CRUD
$client->templates();  // template CRUD
$client->messageLog(); // message history
```

### Sending Messages

#### `messages()->send(campaignKey, templateKey, recipientAddress, options): array`

Send a message immediately. The Hub processes it asynchronously and returns a 202 response. The channel is determined from the resolved template.

```php
$response = $client->messages()->send(
    'welcome-email',        // campaign key
    'welcome_email_en',     // template key (unique within the campaign)
    'customer@example.com', // recipient address
    [
        'recipient_name' => 'Jane Doe',           // optional
        'recipient_ref'  => 'user-123',            // optional, for history lookups
        'data'           => ['name' => 'Jane'],    // optional, template variables
        'metadata'       => ['source' => 'signup'],// optional, stored with the log entry
    ]
);
// $response: ['message' => 'Message dispatched.']
```

#### `messages()->queue(campaignKey, templateKey, recipientAddress, scheduledAt, options): array`

Schedule a message for future delivery.

```php
$response = $client->messages()->queue(
    'payment-reminder',
    'payment_reminder_sms_en',
    '+1234567890',
    '2026-03-15T10:00:00Z', // ISO 8601
    [
        'data' => ['amount' => 150.00],
    ]
);
// $response: ['message' => 'Message queued.', 'id' => 42]
```

### Campaigns

#### `campaigns()->list(params): array`

```php
$campaigns = $client->campaigns()->list(['active' => true, 'per_page' => 25]);
```

#### `campaigns()->get(id): array`

```php
$campaign = $client->campaigns()->get(1);
```

#### `campaigns()->create(data): array`

```php
$campaign = $client->campaigns()->create([
    'key'          => 'order-shipped',
    'name'         => 'Order Shipped Notification',
    'channels'     => ['email', 'sms'],
    'trigger_type' => 'event',
    'category'     => 'operational',
]);
```

#### `campaigns()->update(id, data): array`

```php
$campaign = $client->campaigns()->update(1, ['is_active' => false]);
```

#### `campaigns()->delete(id): void`

```php
$client->campaigns()->delete(1);
```

### Templates

#### `templates()->listForCampaign(campaignId, params): array`

```php
$templates = $client->templates()->listForCampaign(1, ['channel' => 'email']);
```

#### `templates()->get(id): array`

```php
$template = $client->templates()->get(5);
```

#### `templates()->create(campaignId, data): array`

```php
$template = $client->templates()->create(1, [
    'key'       => 'order_shipped_email_en',
    'channel'   => 'email',
    'locale'    => 'en',
    'subject'   => 'Your order has shipped!',
    'body'      => '<h1>Hi {{ name }}</h1><p>Your order #{{ orderId }} is on its way.</p>',
    'is_active' => true,
]);
```

#### `templates()->update(id, data): array`

```php
$template = $client->templates()->update(5, [
    'subject' => 'Updated subject line',
]);
```

#### `templates()->delete(id): void`

```php
$client->templates()->delete(5);
```

### Message History

#### `messageLog()->list(params): array`

```php
$messages = $client->messageLog()->list([
    'campaign_id' => 1,
    'channel'     => 'email',
    'status'      => 'delivered',
    'per_page'    => 50,
]);
```

#### `messageLog()->recipientHistory(ref, params): array`

```php
$history = $client->messageLog()->recipientHistory('user-123', ['per_page' => 10]);
```

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
