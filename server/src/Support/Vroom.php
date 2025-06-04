<?php

namespace Fleetbase\Vroom\Support;

use Fleetbase\Vroom\Exceptions\VroomApiException;
use Illuminate\Support\Facades\Http;

/**
 * Simple API wrapper for the Vroom vehicle routing service.
 */
class Vroom
{
    /**
     * Base URI for Vroom endpoints.
     */
    protected string $baseUri;

    /**
     * API key for authentication.
     */
    protected ?string $apiKey = null;

    /**
     * Initialize base URI and API key from config.
     */
    public function __construct()
    {
        $this->baseUri = config('vroom.base_uri', 'https://api.verso-optim.com/vrp/v1');
    }

    public function setApiKey(?string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Computes a solution for a vehicle routing problem.
     *
     * @param array $payload vehicles, jobs, shipments, etc
     *
     * @throws \Exception on error
     */
    public function solve(array $payload): array
    {
        return $this->post('solve', $payload);
    }

    /**
     * Evaluates pre-ordered routes and returns ETAs and violations.
     *
     * @param array $payload pre-ordered routes array, etc
     *
     * @throws \Exception on error
     */
    public function plan(array $payload): array
    {
        return $this->post('plan', $payload);
    }

    /**
     * Internal helper to perform POST requests.
     *
     * @param string $endpoint "solve" or "plan"
     * @param array  $payload  Request body parameters
     *
     * @return array Parsed JSON response
     *
     * @throws \Exception on non-successful response
     */
    protected function post(string $endpoint, array $payload): array
    {
        $response = Http::timeout(30)->post("{$this->baseUri}/{$endpoint}?api_key={$this->apiKey}", $payload);

        if (!$response->successful()) {
            throw new VroomApiException($endpoint, $response);
        }

        return $response->json();
    }
}
