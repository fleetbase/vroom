<?php

namespace Fleetbase\Vroom\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Models\Setting;
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

        $this->vroom->setApiKey(Utils::getVroomSetting('api_key'));

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

        $this->vroom->setApiKey(Utils::getVroomSetting('api_key'));

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
        $vroomSettings = Setting::lookupCompany('vroom', ['api_key' => null]);

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
        $apiKey = $request->input('api_key');
        Setting::configureCompany('vroom', ['api_key' => $apiKey]);

        return response()->json([
            'status'  => 'ok',
            'message' => 'VROOM settings succesfully saved.',
        ]);
    }
}
