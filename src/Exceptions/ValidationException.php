<?php

declare(strict_types=1);

namespace MessagingHub\Sdk\Exceptions;

use Throwable;

/**
 * Exception thrown when the API returns a 422 Unprocessable Entity response.
 *
 * Contains the field-level validation errors returned by the API,
 * accessible via the getErrors() method.
 */
class ValidationException extends ApiException
{
    /** @var array<string, array<string>> Field-level validation errors keyed by field name */
    private array $errors;

    /**
     * Create a new validation exception.
     *
     * @param string                       $message  Human-readable error description
     * @param array<string, array<string>> $errors   Field-level validation errors from the API response
     * @param Throwable|null               $previous The underlying exception, if any
     */
    public function __construct(string $message = 'Validation failed.', array $errors = [], ?Throwable $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, 422, $previous);
    }

    /**
     * Get the field-level validation errors.
     *
     * Returns an associative array where keys are field names and values
     * are arrays of error messages for that field.
     *
     * Example:
     *   [
     *       'campaign_key' => ['The campaign key field is required.'],
     *       'channel'      => ['The selected channel is invalid.'],
     *   ]
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
