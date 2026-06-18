<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

/**
 * Resource class for sending and queuing messages via the Yolio API.
 *
 * Provides methods to dispatch messages immediately (async on the server side)
 * or schedule them for deferred delivery via the message queue.
 */
class MessageResource extends BaseResource
{
    /**
     * Send a message immediately.
     *
     * The API dispatches the message asynchronously and returns 202 Accepted.
     * The template is resolved by its unique key within the campaign, and the
     * channel is determined from the resolved template.
     *
     * @param string $campaignKey      The unique campaign key identifying which message to send
     * @param string $templateKey      The unique template key within the campaign
     * @param string $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param array  $options          Optional parameters:
     *                                 - recipient_name (string): Display name of the recipient
     *                                 - recipient_ref (string): Unique reference for the recipient (e.g. user ID)
     *                                 - data (array): Template placeholder values
     *                                 - metadata (array): Arbitrary metadata to store with the message log
     *                                 - from (string): SMS sender phone number (E.164) or label. Required when tenant has multi-number SMS enabled.
     *                                 - from_email (string): Per-message sender email (email channel). Must be an approved sender domain.
     *                                 - from_name (string): Per-message sender display name (email channel)
     *                                 - reply_to_email (string): Per-message reply-to email (email channel)
     *                                 - reply_to_name (string): Per-message reply-to display name (email channel)
     *                                 - attachments (array): List of ['filename' => string, 'content' => base64 string, 'content_type' => string]
     *
     * @return array The API response with message ID and status
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function send(string $campaignKey, string $templateKey, string $recipientAddress, array $options = []): array
    {
        $payload = array_merge($options, [
            'campaign_key'      => $campaignKey,
            'template_key'      => $templateKey,
            'recipient_address' => $recipientAddress,
        ]);

        return $this->request('POST', 'api/v1/send', [
            'json' => $payload,
        ]);
    }

    /**
     * Queue a message for scheduled delivery.
     *
     * The message is placed in the message queue and will be dispatched
     * at the specified time by the server's queue processor.
     *
     * @param string $campaignKey      The unique campaign key identifying which message to send
     * @param string $templateKey      The unique template key within the campaign
     * @param string $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param string $scheduledAt      ISO 8601 datetime when the message should be sent
     * @param array  $options          Optional parameters:
     *                                 - recipient_name (string): Display name of the recipient
     *                                 - recipient_ref (string): Unique reference for the recipient (e.g. user ID)
     *                                 - data (array): Template placeholder values
     *                                 - metadata (array): Arbitrary metadata to store with the message log
     *                                 - from (string): SMS sender phone number (E.164) or label. Required when tenant has multi-number SMS enabled.
     *                                 - from_email (string): Per-message sender email (email channel). Must be an approved sender domain.
     *                                 - from_name (string): Per-message sender display name (email channel)
     *                                 - reply_to_email (string): Per-message reply-to email (email channel)
     *                                 - reply_to_name (string): Per-message reply-to display name (email channel)
     *                                 - attachments (array): List of ['filename' => string, 'content' => base64 string, 'content_type' => string]
     *
     * @return array The API response with queued message ID, status, and scheduled time
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function queue(
        string $campaignKey,
        string $templateKey,
        string $recipientAddress,
        string $scheduledAt,
        array $options = []
    ): array {
        $payload = array_merge($options, [
            'campaign_key'      => $campaignKey,
            'template_key'      => $templateKey,
            'recipient_address' => $recipientAddress,
            'scheduled_at'      => $scheduledAt,
        ]);

        return $this->request('POST', 'api/v1/queue', [
            'json' => $payload,
        ]);
    }

    /**
     * Send a batch of messages immediately.
     *
     * Each message is an associative array with the same keys accepted by
     * send() as a flat payload: campaign_key, template_key, recipient_address,
     * and any of the optional fields (data, metadata, from, from_email,
     * from_name, reply_to_email, reply_to_name, attachments). The API validates
     * and dispatches each item independently and returns HTTP 207 with a
     * per-item results array.
     *
     * @param array $messages A list of message payload arrays (max 100)
     *
     * @return array The API response with accepted/rejected counts and per-item results
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 (envelope or budget invalid)
     * @throws ApiException            When the API returns an error
     */
    public function sendBatch(array $messages): array
    {
        return $this->request('POST', 'api/v1/send/batch', [
            'json' => ['messages' => $messages],
        ]);
    }

    /**
     * Queue a batch of messages for scheduled delivery.
     *
     * Each message is an associative array with the same keys accepted by
     * queue() as a flat payload, including a per-item scheduled_at. The API
     * validates and queues each item independently and returns HTTP 207 with a
     * per-item results array (including the queue ID for each accepted item).
     *
     * @param array $messages A list of message payload arrays (max 100), each with scheduled_at
     *
     * @return array The API response with accepted/rejected counts and per-item results
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 (envelope or budget invalid)
     * @throws ApiException            When the API returns an error
     */
    public function queueBatch(array $messages): array
    {
        return $this->request('POST', 'api/v1/queue/batch', [
            'json' => ['messages' => $messages],
        ]);
    }
}
