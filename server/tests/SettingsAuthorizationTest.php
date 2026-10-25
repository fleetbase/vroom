<?php

namespace Fleetbase\Vroom\Tests;

use Fleetbase\Models\Setting;
use Fleetbase\Models\User;
use Fleetbase\Vroom\Http\Controllers\VroomController;
use Fleetbase\Vroom\Support\Authorization;
use Fleetbase\Vroom\Support\Vroom;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class SettingsAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->delete();
        app('session')->put('company', 'company-1');
        config(['vroom.api_key' => null]);
    }

    protected function tearDown(): void
    {
        Authorization::resolveUserUsing(null);
        Authorization::resolvePermissionsUsing(null);
        app('session')->flush();

        parent::tearDown();
    }

    public static function adminMethods(): array
    {
        return [['getAdminSettings'], ['saveAdminSettings']];
    }

    /**
     * @dataProvider adminMethods
     */
    public function testAdminSettingsRefuseUsersWhoAreNotSystemAdministrators(string $method): void
    {
        $this->signInAs($this->user(), ['fleet-ops update routing-settings', 'fleet-ops view routing-settings']);

        $response = $this->call($method, ['api_host' => 'https://attacker.example.com']);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertNull(Setting::lookup('vroom'));
    }

    /**
     * @dataProvider adminMethods
     */
    public function testAdminSettingsRefuseRequestsWithoutAUser(string $method): void
    {
        $this->signInAs(null);

        $this->assertSame(401, $this->call($method)->getStatusCode());
    }

    public function testAdminSettingsNeverReturnTheStoredOrEnvironmentApiKey(): void
    {
        $this->signInAs($this->user('admin'));
        config(['vroom.api_key' => 'env-secret']);
        Setting::configure('vroom', ['api_key' => 'stored-secret', 'api_host' => 'https://routing.example.com/vroom', 'endpoint_mode' => 'binary']);

        $response = $this->call('getAdminSettings');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('stored-secret', $response->getContent());
        $this->assertStringNotContainsString('env-secret', $response->getContent());
        $this->assertSame([
            'api_host'                => 'https://routing.example.com/vroom',
            'endpoint_mode'           => 'binary',
            'has_api_key'             => true,
            'has_environment_api_key' => true,
        ], $response->getData(true));
    }

    public function testAdminSettingsReportAnUnsetKey(): void
    {
        $this->signInAs($this->user('admin'));

        $data = $this->call('getAdminSettings')->getData(true);

        $this->assertFalse($data['has_api_key']);
        $this->assertFalse($data['has_environment_api_key']);
        $this->assertArrayNotHasKey('api_key', $data);
    }

    public static function blankKeys(): array
    {
        return ['missing' => ['missing'], 'null' => [null], 'empty' => [''], 'whitespace' => ['   ']];
    }

    /**
     * @dataProvider blankKeys
     */
    public function testSavingAdminSettingsWithABlankKeyKeepsTheStoredKey(?string $submitted): void
    {
        $this->signInAs($this->user('admin'));
        Setting::configure('vroom', ['api_host' => 'https://old.example.com', 'api_key' => 'stored-secret']);

        $input = ['api_host' => 'https://new.example.com'];
        if ($submitted !== 'missing') {
            $input['api_key'] = $submitted;
        }

        $this->assertSame(200, $this->call('saveAdminSettings', $input)->getStatusCode());
        $this->assertSame(['api_key' => 'stored-secret', 'api_host' => 'https://new.example.com', 'endpoint_mode' => 'saas'], Setting::lookup('vroom'));
    }

    public function testSavingAdminSettingsReplacesOrClearsTheKey(): void
    {
        $this->signInAs($this->user('admin'));
        Setting::configure('vroom', ['api_host' => 'https://old.example.com', 'api_key' => 'stored-secret']);

        $this->call('saveAdminSettings', ['api_host' => 'https://old.example.com', 'api_key' => 'new-secret']);
        $this->assertSame('new-secret', data_get(Setting::lookup('vroom'), 'api_key'));

        $this->call('saveAdminSettings', ['api_host' => 'https://old.example.com', 'api_key' => 'ignored', 'clear_api_key' => true]);
        $this->assertNull(data_get(Setting::lookup('vroom'), 'api_key'));
    }

    public function testSavingAdminSettingsDoesNotCopyTheEnvironmentKeyIntoTheDatabase(): void
    {
        $this->signInAs($this->user('admin'));
        config(['vroom.api_key' => 'env-secret']);

        $this->call('saveAdminSettings', ['api_host' => 'https://routing.example.com']);

        $this->assertNull(data_get(Setting::lookup('vroom'), 'api_key'));
    }

    public static function organizationPermissionCases(): array
    {
        return [
            'read without permission' => ['getSettings', [], 401],
            'read with view'          => ['getSettings', ['fleet-ops view routing-settings'], 200],
            'read with update'        => ['getSettings', ['fleet-ops update routing-settings'], 200],
            'save without permission' => ['saveSettings', [], 401],
            'save with view only'     => ['saveSettings', ['fleet-ops view routing-settings'], 401],
            'save with update'        => ['saveSettings', ['fleet-ops update routing-settings'], 200],
        ];
    }

    /**
     * @dataProvider organizationPermissionCases
     */
    public function testOrganizationSettingsRequireTheRoutingSettingsPermission(string $method, array $permissions, int $status): void
    {
        $this->signInAs($this->user(), $permissions);

        $this->assertSame($status, $this->call($method, ['api_host' => 'https://org.example.com'])->getStatusCode());
    }

    public function testOrganizationSettingsNeverReturnTheApiKey(): void
    {
        $this->signInAs($this->user(), ['fleet-ops view routing-settings']);
        Setting::configureCompany('vroom', ['api_key' => 'org-secret', 'api_host' => 'https://org.example.com', 'endpoint_mode' => 'binary']);

        $response = $this->call('getSettings');

        $this->assertStringNotContainsString('org-secret', $response->getContent());
        $this->assertSame(['api_host' => 'https://org.example.com', 'endpoint_mode' => 'binary', 'has_api_key' => true], $response->getData(true));
    }

    public function testSavingOrganizationSettingsKeepsReplacesAndClearsTheKey(): void
    {
        $this->signInAs($this->user(), ['fleet-ops update routing-settings']);
        Setting::configureCompany('vroom', ['api_host' => 'https://org.example.com', 'api_key' => 'org-secret']);

        $this->call('saveSettings', ['api_host' => 'https://org2.example.com', 'api_key' => '', 'endpoint_mode' => 'binary']);
        $this->assertSame(['api_key' => 'org-secret', 'api_host' => 'https://org2.example.com', 'endpoint_mode' => 'binary'], Setting::lookupCompany('vroom'));

        $this->call('saveSettings', ['api_host' => 'https://org2.example.com', 'api_key' => 'org-secret-2']);
        $this->assertSame('org-secret-2', data_get(Setting::lookupCompany('vroom'), 'api_key'));

        $this->call('saveSettings', ['api_host' => null, 'api_key' => null, 'endpoint_mode' => null, 'clear_api_key' => true]);
        $this->assertSame(['api_key' => null, 'api_host' => null, 'endpoint_mode' => null], Setting::lookupCompany('vroom'));
    }

    public function testSystemAdministratorsPassTheOrganizationPermissionCheck(): void
    {
        $this->signInAs($this->user('admin'));

        $this->assertSame(200, $this->call('saveSettings', ['api_host' => 'https://org.example.com'])->getStatusCode());
    }

    protected function user(string $type = 'user'): User
    {
        return (new User())->forceFill(['type' => $type]);
    }

    protected function signInAs(?User $user, array $permissions = []): void
    {
        Authorization::resolveUserUsing(fn () => $user);
        Authorization::resolvePermissionsUsing(fn (string $permission) => in_array($permission, $permissions, true));
    }

    /**
     * Run a controller action through the middleware it registers, as the router would.
     */
    protected function call(string $method, array $input = [])
    {
        $controller = new VroomController(new Vroom());
        $request    = Request::create('/vroom/int/v1/settings', 'POST', $input);
        $pipeline   = fn () => $controller->{$method}($request);

        foreach (array_reverse($controller->getMiddleware()) as $middleware) {
            if (in_array($method, $middleware['options']['only'] ?? [], true)) {
                $next     = $pipeline;
                $pipeline = fn () => $middleware['middleware']($request, fn () => $next());
            }
        }

        try {
            return $pipeline();
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        }
    }
}
