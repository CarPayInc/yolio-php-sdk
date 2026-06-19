<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\Paginated;
use Yolio\Sdk\Dto\Template;
use Yolio\Sdk\Dto\TemplateRequest;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing templates via the Yolio API.
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
     * @return Paginated Paginated list of Template DTOs
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function listForCampaign(int $campaignId, array $params = []): Paginated
    {
        $response = $this->request('GET', "api/v1/campaigns/{$campaignId}/templates", [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [Template::class, 'make']);
    }

    /**
     * Create a new template for a campaign.
     *
     * @param int                   $campaignId The campaign ID to create the template under
     * @param array|TemplateRequest $data       Template data (array or TemplateRequest) including:
     *                          - key (string): Unique template key within the campaign
     *                          - channel (string): The channel (email, sms, push, call)
     *                          - locale (string): The locale code (en, es)
     *                          - subject (string): Email subject line (email channel only)
     *                          - body (string): Template body content
     *
     * @return Template The created template
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create(int $campaignId, $data): Template
    {
        return Template::make($this->request('POST', "api/v1/campaigns/{$campaignId}/templates", [
            'json' => $this->toPayload($data),
        ]));
    }

    /**
     * Get a single template by ID.
     *
     * @param int $id The template ID
     *
     * @return Template The template
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): Template
    {
        return Template::make($this->request('GET', "api/v1/templates/{$id}"));
    }

    /**
     * Update an existing template.
     *
     * @param int                   $id   The template ID
     * @param array|TemplateRequest $data Template fields to update (partial updates supported)
     *
     * @return Template The updated template
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, $data): Template
    {
        return Template::make($this->request('PUT', "api/v1/templates/{$id}", [
            'json' => $this->toPayload($data),
        ]));
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
