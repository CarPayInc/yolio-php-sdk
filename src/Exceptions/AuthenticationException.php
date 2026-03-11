<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Exceptions;

use Throwable;

/**
 * Exception thrown when the API returns a 401 Unauthorized response.
 *
 * This typically indicates an invalid, expired, or missing API key.
 */
class AuthenticationException extends ApiException
{
    /**
     * Create a new authentication exception.
     *
     * @param string         $message  Human-readable error description
     * @param Throwable|null $previous The underlying exception, if any
     */
    public function __construct(string $message = 'Authentication failed. Check your API key.', ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
