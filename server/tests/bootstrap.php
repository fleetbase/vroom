<?php

/*
|--------------------------------------------------------------------------
| Test bootstrap
|--------------------------------------------------------------------------
|
| A bare container with just what the settings endpoints touch: config,
| an in-memory SQLite `settings` table, an array session and a JSON
| response factory. No Laravel application or database server is needed.
|
*/

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Http\JsonResponse;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;

require __DIR__ . '/../../server_vendor/autoload.php';

$app = new Container();
Container::setInstance($app);
Facade::setFacadeApplication($app);

$app->instance('config', new Repository([
    'vroom'     => require __DIR__ . '/../config/vroom.php',
    'fleetbase' => ['connection' => ['db' => 'mysql']],
    'api'       => ['cache' => ['enabled' => false]],
]));

$capsule = new Capsule($app);
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], 'mysql');
$capsule->getDatabaseManager()->setDefaultConnection('mysql');
$capsule->setAsGlobal();
$capsule->bootEloquent();
$app->instance('db', $capsule->getDatabaseManager());

$capsule->schema('mysql')->create('settings', function ($table) {
    $table->increments('id');
    $table->string('key')->unique();
    $table->json('value')->nullable();
});

$app->instance('session', new Store('test', new ArraySessionHandler(10)));

$app->instance(ResponseFactory::class, new class {
    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }
});
