<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

use UBC\LTI\Param;

class LtiRealUser extends Model
{
    protected $fillable = ['sub', 'platform_id', 'login_hint', 'name', 'email'];
    /**
     * Return the user specified by the LTI Launch information. Will create the
     * user if it doesn't exist already.
     */
    public static function getFromLaunch(
        int $platformId,
        string $loginHint,
        array $claims
    ): LtiRealUser {
        $info = ['login_hint' => $loginHint];
        if (isset($claims[Param::NAME])) $info['name'] = $claims[Param::NAME];
        if (isset($claims[Param::EMAIL])) $info['email'] =$claims[Param::EMAIL];
        // updating user info on every launch, in case they change
        $user = self::updateOrCreate(
            [
                // according to spec, sub must be the platform's user ID, so
                // it's better for lookup than login_hint
                'sub' => $claims[Param::SUB],
                'platform_id' => $platformId
            ],
            $info
        );
        return $user;
    }

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }
}
