<?php

declare(strict_types=1);

namespace Yolio\Sdk\Resources;

use Yolio\Sdk\Dto\Paginated;
use Yolio\Sdk\Dto\PartialRequest;
use Yolio\Sdk\Dto\TemplatePartial;
use Yolio\Sdk\Exceptions\ApiException;
use Yolio\Sdk\Exceptions\AuthenticationException;
use Yolio\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing template partials via the Yolio API.
 *
 * Template partials are reusable content fragments that can be included
 * in templates (e.g. headers, footers, signature blocks).
 */
class PartialResource extends BaseResource
{
    /**
     * List all template partials for the authenticated tenant.
     *
     * @param array $params Optional query parameters for filtering/pagination:
     *                      - page (int): Page number
     *                      - per_page (int): Results per page
     *
     * @return Paginated Paginated list of TemplatePartial DTOs
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): Paginated
    {
        $response = $this->request('GET', 'api/v1/partials', [
            'query' => $params,
        ]);

        return Paginated::fromArray($response, [TemplatePartial::class, 'make']);
    }

    /**
     * Create a new template partial.
     *
     * @param array|PartialRequest $data Partial data (array or PartialRequest) including:
     *                    - key (string): Unique partial key within the tenant
     *                    - body (string): Partial body content
     *                    - description (string): Optional description
     *                    - is_active (bool): Whether the partial is active
     *
     * @return TemplatePartial The created partial
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create($data): TemplatePartial
    {
        return TemplatePartial::make($this->request('POST', 'api/v1/partials', [
            'json' => $this->toPayload($data),
        ]));
    }

    /**
     * Get a single template partial by ID.
     *
     * @param int $id The partial ID
     *
     * @return TemplatePartial The partial
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): TemplatePartial
    {
        return TemplatePartial::make($this->request('GET', "api/v1/partials/{$id}"));
    }

    /**
     * Update an existing template partial.
     *
     * @param int                  $id   The partial ID
     * @param array|PartialRequest $data Partial fields to update (partial updates supported)
     *
     * @return TemplatePartial The updated partial
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, $data): TemplatePartial
    {
        return TemplatePartial::make($this->request('PUT', "api/v1/partials/{$id}", [
            'json' => $this->toPayload($data),
        ]));
    }

    /**
     * Delete a template partial.
     *
     * @param int $id The partial ID
     *
     * @return void
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function delete(int $id): void
    {
        $this->request('DELETE', "api/v1/partials/{$id}");
    }
}
