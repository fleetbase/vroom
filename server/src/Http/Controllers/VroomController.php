<?php

namespace Fleetbase\Vroom\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Vroom\Exceptions\VroomApiException;
use Fleetbase\Vroom\Support\Utils;
use Fleetbase\Vroom\Support\Vroom;
use Illuminate\Http\Request;

class VroomController extends Controller
{
    /**
     * The Vroom API wrapper instance.
     */
    protected Vroom $vroom;

    /**
     * Inject the Vroom support class.
     */
    public function __construct(Vroom $vroom)
    {
        $this->vroom = $vroom;
    }

    /**
     * Solve a vehicle routing problem.
     *
     * Endpoint: POST /vroom/int/v1/solve
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function solve(Request $request)
    {
        $payload = $request->all();

        $this->vroom
            ->setApiKey(Utils::resolveApiKey())
            ->setBaseUri(Utils::resolveBaseUri())
            ->setEndpointMode(Utils::resolveEndpointMode());

        try {
            $result = $this->vroom->solve($payload);

            return response()->json($result);
        } catch (VroomApiException $e) {
            $error = $e->getErrorData();

            return response()->error($error['error'] ?? $e->getMessage(), $e->getStatusCode() ?? 400);
        } catch (\Exception $e) {
            return response()->error(config('app.debug') ? $e->getMessage() : 'VROOM API request failed.');
        }
    }

    /**
     * Plan pre-ordered routes and get ETAs.
     *
     * Endpoint: POST /vroom/int/v1/plan
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function plan(Request $request)
    {
        $payload = $request->all();

        $this->vroom
            ->setApiKey(Utils::resolveApiKey())
            ->setBaseUri(Utils::resolveBaseUri())
            ->setEndpointMode(Utils::resolveEndpointMode());

        try {
            $result = $this->vroom->plan($payload);

            return response()->json($result);
        } catch (VroomApiException $e) {
            $error = $e->getErrorData();

            return response()->error($error['error'] ?? $e->getMessage(), $e->getStatusCode() ?? 400);
        } catch (\Exception $e) {
            return response()->error(config('app.debug') ? $e->getMessage() : 'VROOM API request failed.');
        }
    }

    /**
     * Get VROOM settings.
     *
     * Endpoint: GET /vroom/int/v1/settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $vroomSettings = Utils::getOrganizationSettings([
            'api_key'       => null,
            'api_host'      => null,
            'endpoint_mode' => null,
        ]);

        return response()->json($vroomSettings);
    }

    public function getAdminSettings()
    {
        $vroomSettings = Utils::getSystemSettings([
            'api_key'       => config('vroom.api_key', env('VROOM_API_KEY')),
            'api_host'      => config('vroom.base_uri', env('VROOM_HOST', 'https://api.verso-optim.com/vrp/v1')),
            'endpoint_mode' => config('vroom.endpoint_mode', env('VROOM_ENDPOINT_MODE', 'saas')),
        ]);

        return response()->json($vroomSettings);
    }

    /**
     * Save VROOM settings.
     *
     * Endpoint: POST /vroom/int/v1/settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSettings(Request $request)
    {
        $settings = [
            'api_key'       => $request->input('api_key'),
            'api_host'      => $request->input('api_host'),
            'endpoint_mode' => $request->input('endpoint_mode'),
        ];
        \Fleetbase\Models\Setting::configureCompany('vroom', $settings);

        return response()->json([
            'status'  => 'ok',
            'message' => 'VROOM settings succesfully saved.',
        ]);
    }

    public function saveAdminSettings(Request $request)
    {
        $settings = [
            'api_key'       => $request->input('api_key', config('vroom.api_key', env('VROOM_API_KEY'))),
            'api_host'      => $request->input('api_host', config('vroom.base_uri', env('VROOM_HOST', 'https://api.verso-optim.com/vrp/v1'))),
            'endpoint_mode' => $request->input('endpoint_mode', config('vroom.endpoint_mode', env('VROOM_ENDPOINT_MODE', 'saas'))),
        ];
        \Fleetbase\Models\Setting::configure('vroom', $settings);

        return response()->json([
            'status'  => 'ok',
            'message' => 'VROOM settings succesfully saved.',
        ]);
    }
}
