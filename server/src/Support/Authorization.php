<?php

namespace Fleetbase\Vroom\Support;

use Fleetbase\Models\Permission;
use Fleetbase\Support\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Permission checks for the VROOM settings endpoints.
 *
 * Mirrors Fleet-Ops' own settings authorization:
 *  - platform admins always pass;
 *  - a permission is granted by its exact name, `{service} * {resource}` or `{service} *`;
 *  - while the resource has no permission rows at all (the schema has not been
 *    seeded yet) the check passes, exactly like an unguarded resource.
 */
class Authorization
{
    /**
     * Optional overrides used by the test suite.
     */
    protected static ?\Closure $userResolver       = null;
    protected static ?\Closure $permissionResolver = null;

    /**
     * Abort with a 401 JSON response unless the user holds at least one of the permissions.
     *
     * @param string ...$permissions Full form `{service} {action} {resource}`, e.g. `fleet-ops update routing-settings`
     *
     * @throws HttpResponseException
     */
    public static function authorize(string ...$permissions): void
    {
        if (static::canAny(...$permissions)) {
            return;
        }

        throw new HttpResponseException(response()->json(['errors' => ['User is not authorized to ' . $permissions[0]]], 401));
    }

    /**
     * Abort with a 401 JSON response unless the current user is a platform administrator.
     *
     * @throws HttpResponseException
     */
    public static function authorizeAdmin(): void
    {
        $user = static::user();
        if ($user && $user->isAdmin()) {
            return;
        }

        throw new HttpResponseException(response()->json(['errors' => ['This action requires a system administrator']], 401));
    }

    public static function canAny(string ...$permissions): bool
    {
        $user = static::user();
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (static::can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function can(string $permission): bool
    {
        if (static::$permissionResolver) {
            return (bool) call_user_func(static::$permissionResolver, $permission);
        }

        [$service, , $resource] = explode(' ', $permission, 3);

        // Same test as Auth::isResourceGuarded(), scoped to the service: no permission rows for the resource yet.
        if (!Permission::where('name', 'like', $service . ' % ' . $resource)->exists()) {
            return true;
        }

        try {
            return Auth::can($permission);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function resolveUserUsing(?\Closure $resolver): void
    {
        static::$userResolver = $resolver;
    }

    public static function resolvePermissionsUsing(?\Closure $resolver): void
    {
        static::$permissionResolver = $resolver;
    }

    protected static function user()
    {
        if (static::$userResolver) {
            return call_user_func(static::$userResolver);
        }

        try {
            return Auth::getUserFromSession();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
