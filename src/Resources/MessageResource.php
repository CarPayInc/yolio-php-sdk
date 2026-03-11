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
}
