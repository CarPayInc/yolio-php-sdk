<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\MessageLog;
use Yolio\Sdk\Dto\Paginated;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;

/**
 * Resource class for querying message history via the Yolio API.
 *
 * Provides read-only access to the message log, allowing retrieval of
 * sent messages by various criteria and per-recipient history lookups.
 */
class MessageLogResource extends BaseResource
{
    /**
     * List messages from the message log.
     *
     * @param array $params Optional query parameters to filter results:
     *                      - campaign_id (int): Filter by campaign ID
     *                      - channel (string): Filter by channel (email, sms, push, call)
     *                      - status (string): Filter by delivery status
     *                      - recipient_address (string): Filter by recipient address
     *                      - start_date (string): Lower bound (inclusive) of the created_at window — bare date (YYYY-MM-DD) or ISO 8601 datetime, resolved in the tenant timezone
     *                      - end_date (string): Upper bound of the created_at window — a bare date is inclusive of the whole day
     *                      - per_page (int): Number of results per page
     *
     * @return Paginated Paginated list of MessageLog DTOs
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): Paginated
    {
        $response = $this->request('GET', 'api/v1/messages', [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [MessageLog::class, 'make']);
    }

    /**
     * Get the message history for a specific recipient.
     *
     * Looks up all messages sent to a recipient identified by their unique
     * reference (e.g. user ID or customer number).
     *
     * @param string $ref    The unique recipient reference to look up
     * @param array  $params Optional query parameters:
     *                       - start_date (string): Lower bound (inclusive) of the created_at window — bare date (YYYY-MM-DD) or ISO 8601 datetime, resolved in the tenant timezone
     *                       - end_date (string): Upper bound of the created_at window — a bare date is inclusive of the whole day
     *                       - per_page (int): Number of results per page
     *
     * @return Paginated Paginated list of MessageLog DTOs sent to the recipient
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function recipientHistory(string $ref, array $params = []): Paginated
    {
        $response = $this->request('GET', "api/v1/messages/recipient/{$ref}", [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [MessageLog::class, 'make']);
    }

    /**
     * Look up a single message by its unique message ID.
     *
     * Accepts the `message_id` (ULID) returned from send() or queue(). A
     * dispatched message resolves to its log entry; a still-scheduled message
     * resolves to its queued representation (status "scheduled"/"processing"
     * with delivery fields null).
     *
     * @param string $messageId The unique message identifier (ULID)
     *
     * @return MessageLog The message record (or its queued representation)
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the message is not found (404) or another error occurs
     */
    public function find(string $messageId): MessageLog
    {
        return MessageLog::make($this->request('GET', "api/v1/messages/{$messageId}"));
    }
}
