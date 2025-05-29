<?php

namespace Fleetbase\Vroom\Http\Controllers;

use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Vroom\Exceptions\VroomApiException;
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
     * Endpoint: POST /vroom/solve
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function solve(Request $request)
    {
        $payload = $request->all();

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
     * Endpoint: POST /vroom/plan
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function plan(Request $request)
    {
        $payload = $request->all();

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
}
