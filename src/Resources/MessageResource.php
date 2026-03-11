<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Resource class for sending and queuing messages via the Messaging Hub API.
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
     * The message is resolved against the campaign's template for the given
     * channel and rendered with the provided data.
     *
     * @param string $campaignKey      The unique campaign key identifying which message to send
     * @param string $channel          The delivery channel (email, sms, push, call)
     * @param string $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param array  $options          Optional parameters:
     *                                 - recipient_name (string): Display name of the recipient
     *                                 - recipient_ref (string): Unique reference for the recipient (e.g. user ID)
     *                                 - data (array): Template placeholder values
     *                                 - locale (string): Locale for template resolution (default: en)
     *                                 - metadata (array): Arbitrary metadata to store with the message log
     *
     * @return array The API response with message ID and status
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function send(string $campaignKey, string $channel, string $recipientAddress, array $options = []): array
    {
        $payload = array_merge($options, [
            'campaign_key'      => $campaignKey,
            'channel'           => $channel,
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
     * @param string $channel          The delivery channel (email, sms, push, call)
     * @param string $recipientAddress The recipient's address (email address, phone number, etc.)
     * @param string $scheduledAt      ISO 8601 datetime when the message should be sent
     * @param array  $options          Optional parameters:
     *                                 - recipient_name (string): Display name of the recipient
     *                                 - recipient_ref (string): Unique reference for the recipient (e.g. user ID)
     *                                 - data (array): Template placeholder values
     *                                 - locale (string): Locale for template resolution (default: en)
     *                                 - metadata (array): Arbitrary metadata to store with the message log
     *
     * @return array The API response with queued message ID, status, and scheduled time
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function queue(
        string $campaignKey,
        string $channel,
        string $recipientAddress,
        string $scheduledAt,
        array $options = []
    ): array {
        $payload = array_merge($options, [
            'campaign_key'      => $campaignKey,
            'channel'           => $channel,
            'recipient_address' => $recipientAddress,
            'scheduled_at'      => $scheduledAt,
        ]);

        return $this->request('POST', 'api/v1/queue', [
            'json' => $payload,
        ]);
    }
}
