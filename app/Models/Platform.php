<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Platform extends Model
{
    public function clients()
    {
        return $this->hasMany('App\Models\PlatformClient');
    }

    public function deployments()
    {
        return $this->hasMany('App\Models\Deployment');
    }

    public function keys()
    {
        return $this->hasMany('App\Models\PlatformKey');
    }

    // get the shim's platform entry
    public static function getOwnPlatform(): Platform
    {
        $platform = self::find(config('lti.own_platform_id'));
        if (!$platform) {
            throw new \UnexpectedValueException(
                "Missing own platform information, did you seed the database?");
        }
        return $platform;
    }
}
