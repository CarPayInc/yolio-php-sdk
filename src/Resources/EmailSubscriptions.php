<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\EmailSubscription;
use Yolio\Sdk\Dto\EmailSubscriptionStatus;
use Yolio\Sdk\Dto\Paginated;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

/**
 * Resource class for checking email subscription status via the Yolio API.
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
     * @return Paginated Paginated list of EmailSubscription DTOs
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): Paginated
    {
        $response = $this->request('GET', 'api/v1/email-subscriptions', [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [EmailSubscription::class, 'make']);
    }

    /**
     * Check the opt-out status of a specific email address.
     *
     * @param string $email The email address to check
     *
     * @return EmailSubscriptionStatus The opt-out status for the address
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the email parameter is invalid
     * @throws ApiException            When the API returns an error
     */
    public function check(string $email): EmailSubscriptionStatus
    {
        return EmailSubscriptionStatus::make($this->request('GET', 'api/v1/email-subscriptions/check', [
            'query' => ['email' => $email],
        ]));
    }
}
