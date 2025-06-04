<?php

namespace Fleetbase\Vroom\Support;

use Fleetbase\Models\Setting;
use Fleetbase\Support\Utils as FleetbaseUtils;

class Utils extends FleetbaseUtils
{
    public static function getVroomSetting(string $key, $defaultValue = null)
    {
        $vroomSettings = Setting::lookupCompany('vroom');

        return data_get($vroomSettings, $key, $defaultValue);
    }
}
