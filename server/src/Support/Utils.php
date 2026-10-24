<?php

namespace Fleetbase\Vroom\Support;

use Fleetbase\Models\Setting;
use Fleetbase\Support\Utils as FleetbaseUtils;

class Utils extends FleetbaseUtils
{
    public static function getOrganizationSettings(array $defaults = []): array
    {
        return Setting::lookupCompany('vroom', $defaults);
    }

    public static function getSystemSettings(array $defaults = []): array
    {
        return Setting::lookup('vroom', $defaults);
    }

    public static function resolveSetting(string $key, $defaultValue = null)
    {
        $organizationSettings = static::getOrganizationSettings();
        $organizationValue    = data_get($organizationSettings, $key);

        if (static::hasConfiguredValue($organizationValue)) {
            return $organizationValue;
        }

        $systemSettings = static::getSystemSettings();
        $systemValue    = data_get($systemSettings, $key);

        if (static::hasConfiguredValue($systemValue)) {
            return $systemValue;
        }

        return $defaultValue;
    }

    public static function resolveBaseUri(): string
    {
        return static::resolveSetting('api_host', config('vroom.base_uri', env('VROOM_HOST', 'https://api.verso-optim.com/vrp/v1')));
    }

    public static function resolveApiKey(): ?string
    {
        return static::resolveSetting('api_key', config('vroom.api_key', env('VROOM_API_KEY')));
    }

    public static function resolveEndpointMode(): string
    {
        return static::resolveSetting('endpoint_mode', config('vroom.endpoint_mode', env('VROOM_ENDPOINT_MODE', 'saas')));
    }

    /**
     * Replace the stored API key with a `has_api_key` flag so responses never carry the key.
     */
    public static function withoutApiKey(array $settings): array
    {
        $settings['has_api_key'] = static::hasConfiguredValue(data_get($settings, 'api_key'));
        unset($settings['api_key']);

        return $settings;
    }

    /**
     * The API key to store after a settings save.
     *
     * The console never receives the stored key, so an empty or missing `api_key` keeps the stored one.
     * `clear_api_key` removes it.
     */
    public static function submittedApiKey(array $input, ?string $storedApiKey): ?string
    {
        if (filter_var(data_get($input, 'clear_api_key', false), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $submitted = data_get($input, 'api_key');
        if (is_string($submitted) && trim($submitted) !== '') {
            return trim($submitted);
        }

        return static::hasConfiguredValue($storedApiKey) ? $storedApiKey : null;
    }

    protected static function hasConfiguredValue($value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }
}
