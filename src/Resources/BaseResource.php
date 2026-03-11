<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Resources;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use MessagingHub\Sdk\Exceptions\ApiException;
use MessagingHub\Sdk\Exceptions\AuthenticationException;
use MessagingHub\Sdk\Exceptions\ValidationException;

/**
 * Base class for all API resource classes.
 *
 * Provides a shared HTTP request method that handles JSON encoding/decoding,
 * error mapping, and exception wrapping for all resource operations.
 */
abstract class BaseResource
{
    /** @var ClientInterface The Guzzle HTTP client instance */
    protected ClientInterface $client;

    /**
     * Create a new resource instance.
     *
     * @param ClientInterface $client The Guzzle HTTP client configured with base URL and auth headers
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Send an HTTP request to the API and return the decoded JSON response.
     *
     * Handles all error mapping:
     * - 401 responses throw AuthenticationException
     * - 422 responses throw ValidationException with field-level errors
     * - Other 4xx/5xx responses throw ApiException with status code
     * - Connection/network errors throw ApiException without status code
     *
     * @param string $method  The HTTP method (GET, POST, PUT, DELETE)
     * @param string $uri     The request URI (relative to base URL)
     * @param array  $options Guzzle request options (query, json, etc.)
     *
     * @return array The decoded JSON response body
     *
     * @throws AuthenticationException When the API returns 401 Unauthorized
     * @throws ValidationException     When the API returns 422 Unprocessable Entity
     * @throws ApiException            When the API returns any other error or a connection failure occurs
     */
    protected function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $body     = $response->getBody()->getContents();

            if ($body === '') {
                return [];
            }

            $decoded = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException(
                    'Failed to decode API response: ' . json_last_error_msg()
                );
            }

            return $decoded;
        } catch (RequestException $e) {
            $response   = $e->getResponse();
            $statusCode = $response !== null ? $response->getStatusCode() : null;
            $body       = [];

            if ($response !== null) {
                $body = json_decode($response->getBody()->getContents(), true) ?? [];
            }

            $message = $body['message'] ?? $e->getMessage();

            if ($statusCode === 401) {
                throw new AuthenticationException($message, $e);
            }

            if ($statusCode === 422) {
                $errors = $body['errors'] ?? [];

                throw new ValidationException($message, $errors, $e);
            }

            throw new ApiException($message, $statusCode, $e);
        } catch (GuzzleException $e) {
            throw new ApiException(
                'API request failed: ' . $e->getMessage(),
                null,
                $e
            );
        }
    }
}
