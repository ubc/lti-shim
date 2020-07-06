<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use UBC\LTI\Param;

class LtiRealUser extends Model
{
    protected $fillable = ['sub', 'platform_id', 'login_hint', 'name', 'email'];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    // Create users based on info returned from NRPS. As far as I can figure,
    // Eloquent doesn't have a bulk create method, so we have to use the DB
    // abstraction's insert(). This assumes that the given list of users are all
    // new users.
    public static function createFromNRPS(
        int $platformId,
        int $toolId,
        array $newUsers
    ): Collection {
        if (empty($newUsers)) return collect([]);
        $userInfos = [];
        $subs = []; // used to retrieve the newly created users
        foreach ($newUsers as $user) {
            $info = [
                'sub' => $user[Param::USER_ID],
                'platform_id' => $platformId
            ];
            if (isset($user[Param::NAME])) $info['name'] = $user[Param::NAME];
            if (isset($user[Param::EMAIL])) $info['email'] =$user[Param::EMAIL];
            $userInfos[] = $info;
            $subs[] = $user[Param::USER_ID];
        }
        // turns out there's no way to bulk insert and get the resulting
        // new records back in Laravel, so we have to do a separate query
        self::insert($userInfos);
        return self::getBySubs($platformId, $subs);
    }

    // Bulk retrieve users identified by the 'sub' field.
    public static function getBySubs(int $platformId, array $subs): Collection
    {
        return self::whereIn('sub', $subs)
                     ->where('platform_id', $platformId)
                     ->get();
    }

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
}
