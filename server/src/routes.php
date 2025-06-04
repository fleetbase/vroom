<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix(config('vroom.api.routing.prefix', 'vroom'))->namespace('Fleetbase\Vroom\Http\Controllers')->group(
    function ($router) {
        /*
        |--------------------------------------------------------------------------
        | Vroom API Routes
        |--------------------------------------------------------------------------
        |
        | Primary internal routes for console.
        */
        $router->prefix(config('vroom.api.routing.internal_prefix', 'int'))->group(
            function ($router) {
                $router->group(
                    ['prefix' => 'v1', 'middleware' => ['fleetbase.protected']],
                    function ($router) {
                        $router->post('solve', 'VroomController@solve');
                        $router->post('plan', 'VroomController@plan');
                        $router->post('settings', 'VroomController@saveSettings');
                        $router->get('settings', 'VroomController@getSettings');
                    }
                );
            }
        );
    }
);
