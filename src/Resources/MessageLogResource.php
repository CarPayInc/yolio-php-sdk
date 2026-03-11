<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;

/**
 * Resource class for querying message history via the Messaging Hub API.
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
     *                      - per_page (int): Number of results per page
     *
     * @return array Paginated list of message log entries
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): array
    {
        return $this->request('GET', 'api/v1/messages', [
            'query' => $params,
        ]);
    }

    /**
     * Get the message history for a specific recipient.
     *
     * Looks up all messages sent to a recipient identified by their unique
     * reference (e.g. user ID or customer number).
     *
     * @param string $ref    The unique recipient reference to look up
     * @param array  $params Optional query parameters:
     *                       - per_page (int): Number of results per page
     *
     * @return array Paginated list of messages sent to the recipient
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function recipientHistory(string $ref, array $params = []): array
    {
        return $this->request('GET', "api/v1/messages/recipient/{$ref}", [
            'query' => $params,
        ]);
    }
}
