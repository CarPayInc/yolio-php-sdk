<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing campaigns via the Messaging Hub API.
 *
 * Provides CRUD operations for campaigns, which are the central unit
 * defining what gets sent, via which channels, and to whom.
 */
class CampaignResource extends BaseResource
{
    /**
     * List all campaigns for the authenticated tenant.
     *
     * @param array $params Optional query parameters to filter results:
     *                      - active (bool): Filter by active status
     *                      - per_page (int): Number of results per page
     *
     * @return array Paginated list of campaigns
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): array
    {
        return $this->request('GET', 'api/v1/campaigns', [
            'query' => $params,
        ]);
    }

    /**
     * Create a new campaign.
     *
     * @param array $data Campaign data including:
     *                    - key (string): Unique campaign key within the tenant
     *                    - name (string): Human-readable campaign name
     *                    - description (string): Campaign description
     *                    - trigger_type (string): "event", "cron", or "manual"
     *                    - category (string): "informational", "operational", or "promotional"
     *                    - channels (array): Enabled channels (e.g. ["email", "sms"])
     *
     * @return array The created campaign data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create(array $data): array
    {
        return $this->request('POST', 'api/v1/campaigns', [
            'json' => $data,
        ]);
    }

    /**
     * Get a single campaign by ID.
     *
     * @param int $id The campaign ID
     *
     * @return array The campaign data including its templates
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): array
    {
        return $this->request('GET', "api/v1/campaigns/{$id}");
    }

    /**
     * Update an existing campaign.
     *
     * @param int   $id   The campaign ID
     * @param array $data Campaign fields to update (partial updates supported)
     *
     * @return array The updated campaign data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, array $data): array
    {
        return $this->request('PUT', "api/v1/campaigns/{$id}", [
            'json' => $data,
        ]);
    }

    /**
     * Delete a campaign.
     *
     * @param int $id The campaign ID
     *
     * @return void
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function delete(int $id): void
    {
        $this->request('DELETE', "api/v1/campaigns/{$id}");
    }
}
