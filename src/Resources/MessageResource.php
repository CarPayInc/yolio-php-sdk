<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\BatchResult;
use Yolio\Sdk\Dto\MessageRequest;
use Yolio\Sdk\Dto\QueueResult;
use Yolio\Sdk\Dto\SendResult;
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
     * Call with a `MessageRequest` for a fully typed payload, or with the
     * positional campaign/template/recipient plus an options array (or
     * `MessageRequest`) carrying the optional fields.
     *
     * @param string|MessageRequest $campaignKey      The campaign key, or a complete MessageRequest
     * @param string                $templateKey      The unique template key within the campaign
     * @param string                $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param array|MessageRequest  $options          Optional parameters:
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
     * @return SendResult The send acknowledgement with the message ID
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function send($campaignKey, string $templateKey = '', string $recipientAddress = '', $options = []): SendResult
    {
        $payload = $this->messagePayload($campaignKey, $templateKey, $recipientAddress, $options);

        return SendResult::make($this->request('POST', 'api/v1/send', [
            'json' => $payload,
        ]));
    }

    /**
     * Queue a message for scheduled delivery.
     *
     * The message is placed in the message queue and will be dispatched
     * at the specified time by the server's queue processor.
     *
     * Call with a `MessageRequest` (including `scheduled_at`) for a fully typed
     * payload, or with the positional campaign/template/recipient/scheduledAt
     * plus an options array (or `MessageRequest`).
     *
     * @param string|MessageRequest $campaignKey      The campaign key, or a complete MessageRequest (with scheduled_at)
     * @param string                $templateKey      The unique template key within the campaign
     * @param string                $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param string                $scheduledAt      ISO 8601 datetime when the message should be sent
     * @param array|MessageRequest  $options          Optional parameters (see send())
     *
     * @return QueueResult The queue acknowledgement with the queue ID and message ID
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function queue(
        $campaignKey,
        string $templateKey = '',
        string $recipientAddress = '',
        string $scheduledAt = '',
        $options = []
    ): QueueResult {
        $payload = $this->messagePayload($campaignKey, $templateKey, $recipientAddress, $options);

        if ($scheduledAt !== '') {
            $payload['scheduled_at'] = $scheduledAt;
        }

        return QueueResult::make($this->request('POST', 'api/v1/queue', [
            'json' => $payload,
        ]));
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
     * @param array<int, array|MessageRequest> $messages A list of message payloads (arrays or MessageRequest), max 100
     *
     * @return BatchResult The accepted/rejected counts and per-item results
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 (envelope or budget invalid)
     * @throws ApiException            When the API returns an error
     */
    public function sendBatch(array $messages): BatchResult
    {
        return BatchResult::make($this->request('POST', 'api/v1/send/batch', [
            'json' => ['messages' => $this->normalizeMessages($messages)],
        ]));
    }

    /**
     * Queue a batch of messages for scheduled delivery.
     *
     * Each message is an associative array with the same keys accepted by
     * queue() as a flat payload, including a per-item scheduled_at. The API
     * validates and queues each item independently and returns HTTP 207 with a
     * per-item results array (including the queue ID for each accepted item).
     *
     * @param array<int, array|MessageRequest> $messages A list of message payloads (arrays or MessageRequest), each with scheduled_at; max 100
     *
     * @return BatchResult The accepted/rejected counts and per-item results
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 (envelope or budget invalid)
     * @throws ApiException            When the API returns an error
     */
    public function queueBatch(array $messages): BatchResult
    {
        return BatchResult::make($this->request('POST', 'api/v1/queue/batch', [
            'json' => ['messages' => $this->normalizeMessages($messages)],
        ]));
    }

    /**
     * Build the request payload for a single send/queue call.
     *
     * Accepts either a complete MessageRequest (used as-is) or positional
     * campaign/template/recipient plus an options array or MessageRequest.
     *
     * @param string|MessageRequest $campaignKey      The campaign key or a complete MessageRequest
     * @param string                $templateKey      The template key (positional form)
     * @param string                $recipientAddress The recipient address (positional form)
     * @param array|MessageRequest  $options          The optional fields (positional form)
     *
     * @return array<string, mixed>
     */
    private function messagePayload($campaignKey, string $templateKey, string $recipientAddress, $options): array
    {
        if ($campaignKey instanceof MessageRequest) {
            return $campaignKey->toArray();
        }

        $payload = $this->toPayload($options);
        $payload['campaign_key']      = $campaignKey;
        $payload['template_key']      = $templateKey;
        $payload['recipient_address'] = $recipientAddress;

        return $payload;
    }

    /**
     * Normalize a list of batch message items to wire-format arrays.
     *
     * @param array<int, array|MessageRequest> $messages
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMessages(array $messages): array
    {
        return array_map(fn ($message) => $this->toPayload($message), $messages);
    }
}
