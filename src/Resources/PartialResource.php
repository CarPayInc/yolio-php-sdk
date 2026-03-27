<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Resource class for managing template partials via the Messaging Hub API.
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
     * @return array Paginated list of template partials
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error
     */
    public function list(array $params = []): array
    {
        return $this->request('GET', 'api/v1/partials', [
            'query' => $params,
        ]);
    }

    /**
     * Create a new template partial.
     *
     * @param array $data Partial data including:
     *                    - key (string): Unique partial key within the tenant
     *                    - body (string): Partial body content
     *                    - description (string): Optional description
     *                    - is_active (bool): Whether the partial is active
     *
     * @return array The created partial data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function create(array $data): array
    {
        return $this->request('POST', 'api/v1/partials', [
            'json' => $data,
        ]);
    }

    /**
     * Get a single template partial by ID.
     *
     * @param int $id The partial ID
     *
     * @return array The partial data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ApiException            When the API returns an error (e.g. 404 Not Found)
     */
    public function get(int $id): array
    {
        return $this->request('GET', "api/v1/partials/{$id}");
    }

    /**
     * Update an existing template partial.
     *
     * @param int   $id   The partial ID
     * @param array $data Partial fields to update (partial updates supported)
     *
     * @return array The updated partial data
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 with validation errors
     * @throws ApiException            When the API returns an error
     */
    public function update(int $id, array $data): array
    {
        return $this->request('PUT', "api/v1/partials/{$id}", [
            'json' => $data,
        ]);
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
