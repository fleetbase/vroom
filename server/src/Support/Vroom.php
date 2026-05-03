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
     * Endpoint mode for request construction.
     */
    protected string $endpointMode = 'saas';

    /**
     * Initialize base URI and API key from config.
     */
    public function __construct()
    {
        $this->baseUri = config('vroom.base_uri', 'https://api.verso-optim.com/vrp/v1');
        $this->endpointMode = config('vroom.endpoint_mode', 'saas');
    }

    public function setApiKey(?string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setBaseUri(?string $baseUri)
    {
        if ($baseUri) {
            $this->baseUri = $baseUri;
        }

        return $this;
    }

    public function setEndpointMode(?string $endpointMode)
    {
        $this->endpointMode = in_array($endpointMode, ['saas', 'binary']) ? $endpointMode : 'saas';

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
        $url = $this->buildEndpointUrl($endpoint);
        $request = Http::timeout(30);

        if ($this->apiKey) {
            $request = $request->withQueryParameters(['api_key' => $this->apiKey]);
        }

        $response = $request->post($url, $payload);

        if (!$response->successful()) {
            throw new VroomApiException($endpoint, $response);
        }

        return $response->json();
    }

    protected function buildEndpointUrl(string $endpoint): string
    {
        $baseUri = rtrim($this->baseUri, '/');

        if ($this->endpointMode === 'binary') {
            return $baseUri;
        }

        return "{$baseUri}/{$endpoint}";
    }
}
