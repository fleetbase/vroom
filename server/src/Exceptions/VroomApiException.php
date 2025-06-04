<?php

namespace Fleetbase\Vroom\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Exception thrown when a Vroom API request fails.
 */
class VroomApiException extends \Exception
{
    /**
     * HTTP status code returned by the API.
     */
    protected int $statusCode;

    /**
     * Parsed error data from the API response.
     */
    protected ?array $errorData;

    /**
     * Create a new VroomApiException.
     *
     * @param string   $endpoint The API endpoint that was called (e.g. "solve" or "plan").
     * @param Response $response the HTTP client response instance
     */
    public function __construct(string $endpoint, Response $response)
    {
        $this->statusCode = $response->status();
        $body             = $response->body();

        // Attempt to parse JSON error data
        $parsed = null;
        try {
            $parsed = $response->json();
        } catch (\Throwable $e) {
            // leave as null if parsing fails
        }
        $this->errorData = $parsed;

        $message = sprintf(
            'Vroom API [%s] request failed [%d]: %s',
            $endpoint,
            $this->statusCode,
            $body
        );

        parent::__construct($message, $this->statusCode);
    }

    /**
     * Get the HTTP status code returned by the API.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the parsed error JSON data from the API, if available.
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}
