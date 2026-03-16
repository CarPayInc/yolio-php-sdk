<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Resource class for checking email subscription status via the Messaging Hub API.
 *
 * Provides read-only access to email opt-out preferences, allowing
 * retrieval of all unsubscriptions and single email lookups.
 */
class EmailSubscriptions extends BaseResource
{
    /**
     * List all email unsubscriptions for the authenticated tenant.
     *
     * @param array $params Optional query parameters:
     *                      - per_page (int): Number of results per page (default 25, max 100)
     *                      - page (int): Page number
     *
     * @return array Paginated list of email unsubscription records
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): array
    {
        return $this->request('GET', 'api/v1/email-subscriptions', [
            'query' => $params,
        ]);
    }

    /**
     * Check the opt-out status of a specific email address.
     *
     * @param string $email The email address to check
     *
     * @return array The subscription status with keys: email, opted_out_categories, is_fully_opted_out
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the email parameter is invalid
     * @throws ApiException            When the API returns an error
     */
    public function check(string $email): array
    {
        return $this->request('GET', 'api/v1/email-subscriptions/check', [
            'query' => ['email' => $email],
        ]);
    }
}
