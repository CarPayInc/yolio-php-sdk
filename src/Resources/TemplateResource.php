<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing templates via the Messaging Hub API.
 *
 * Templates define the content for a campaign and are identified by a unique
 * key within the campaign scope (e.g. "welcome_email_en").
 */
class TemplateResource extends BaseResource
{
    /**
     * List all templates for a given campaign.
     *
     * @param int   $campaignId The campaign ID to list templates for
     * @param array $params     Optional query parameters to filter results:
     *                          - channel (string): Filter by channel (email, sms, push, call)
     *                          - locale (string): Filter by locale (en, es)
     *
     * @return array Paginated list of templates for the campaign
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function listForCampaign(int $campaignId, array $params = []): array
    {
        return $this->request('GET', "api/v1/campaigns/{$campaignId}/templates", [
            'query' => $params,
        ]);
    }

    /**
     * Create a new template for a campaign.
     *
     * @param int   $campaignId The campaign ID to create the template under
     * @param array $data       Template data including:
     *                          - key (string): Unique template key within the campaign
     *                          - channel (string): The channel (email, sms, push, call)
     *                          - locale (string): The locale code (en, es)
     *                          - subject (string): Email subject line (email channel only)
     *                          - body (string): Template body content
     *                          - blade_template (string): Blade template path (email channel only)
     *
     * @return array The created template data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create(int $campaignId, array $data): array
    {
        return $this->request('POST', "api/v1/campaigns/{$campaignId}/templates", [
            'json' => $data,
        ]);
    }

    /**
     * Get a single template by ID.
     *
     * @param int $id The template ID
     *
     * @return array The template data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): array
    {
        return $this->request('GET', "api/v1/templates/{$id}");
    }

    /**
     * Update an existing template.
     *
     * @param int   $id   The template ID
     * @param array $data Template fields to update (partial updates supported)
     *
     * @return array The updated template data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, array $data): array
    {
        return $this->request('PUT', "api/v1/templates/{$id}", [
            'json' => $data,
        ]);
    }

    /**
     * Delete a template.
     *
     * @param int $id The template ID
     *
     * @return void
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function delete(int $id): void
    {
        $this->request('DELETE', "api/v1/templates/{$id}");
    }
}
