<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\Campaign;
use Yolio\Sdk\Dto\CampaignRequest;
use Yolio\Sdk\Dto\Paginated;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing campaigns via the Yolio API.
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
     * @return Paginated Paginated list of Campaign DTOs
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): Paginated
    {
        $response = $this->request('GET', 'api/v1/campaigns', [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [Campaign::class, 'make']);
    }

    /**
     * Create a new campaign.
     *
     * @param array|CampaignRequest $data Campaign data (array or CampaignRequest) including:
     *                    - key (string): Unique campaign key within the tenant
     *                    - name (string): Human-readable campaign name
     *                    - description (string): Campaign description
     *                    - trigger_type (string): "event", "cron", or "manual"
     *                    - category (string): "informational", "operational", or "promotional"
     *                    - channels (array): Enabled channels (e.g. ["email", "sms"])
     *
     * @return Campaign The created campaign
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create($data): Campaign
    {
        return Campaign::make($this->request('POST', 'api/v1/campaigns', [
            'json' => $this->toPayload($data),
        ]));
    }

    /**
     * Get a single campaign by ID.
     *
     * @param int $id The campaign ID
     *
     * @return Campaign The campaign including its templates
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): Campaign
    {
        return Campaign::make($this->request('GET', "api/v1/campaigns/{$id}"));
    }

    /**
     * Update an existing campaign.
     *
     * @param int                   $id   The campaign ID
     * @param array|CampaignRequest $data Campaign fields to update (partial updates supported)
     *
     * @return Campaign The updated campaign
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, $data): Campaign
    {
        return Campaign::make($this->request('PUT', "api/v1/campaigns/{$id}", [
            'json' => $this->toPayload($data),
        ]));
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
