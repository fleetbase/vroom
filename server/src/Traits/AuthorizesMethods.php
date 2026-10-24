<?php

namespace Fleetbase\Vroom\Traits;

use Fleetbase\Vroom\Support\Authorization;

/**
 * Registers controller middleware that enforces an explicit permission per method.
 *
 * Map values are one permission (`fleet-ops update routing-settings`), a list of
 * permissions of which the user needs any one, or `admin` for system administrators.
 */
trait AuthorizesMethods
{
    /**
     * @param array<string, string|string[]> $map method name => permission(s)
     */
    protected function authorizeMethods(array $map): void
    {
        foreach ($map as $method => $permissions) {
            $this->middleware(function ($request, $next) use ($permissions) {
                if ($permissions === 'admin') {
                    Authorization::authorizeAdmin();
                } else {
                    Authorization::authorize(...(array) $permissions);
                }

                return $next($request);
            })->only($method);
        }
    }
}
