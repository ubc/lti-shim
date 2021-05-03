<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use UBC\LTI\Utils\Param;

class LtiRealUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub',
        'platform_id',
        'login_hint',
        'name',
        'email',
        'student_number'
    ];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    public function lti_fake_users()
    {
        return $this->hasMany('App\Models\LtiFakeUser');
    }

    /**
     * Create/Update users based on info returned from NRPS. Uses the new bulk
     * create or update method upsert()
     */
    public static function upsertFromNrps(
        int $platformId,
        array $users
    ): Collection {
        if (empty($users)) return collect([]);

        $userInfos = [];
        $subs = []; // used to retrieve the newly created users
        foreach ($users as $key => $user) {
            $info = [
                'sub' => $user[Param::USER_ID],
                'platform_id' => $platformId,
                'name' => null,
                'email' => null,
                'student_number' => null
            ];
            if (isset($user[Param::NAME])) $info['name'] = $user[Param::NAME];
            if (isset($user[Param::EMAIL])) $info['email'] =$user[Param::EMAIL];
            if (isset($user[Param::LIS_PERSON_SOURCEDID])) {
                $info['student_number'] =
                    $user[Param::LIS_PERSON_SOURCEDID];
            }
            $userInfos[] = $info;
            $subs[] = $user[Param::USER_ID];
        }

        self::upsert(
            $userInfos, // list of users
            ['sub', 'platform_id'], // columns which uniquely id the user
            ['name', 'email', 'student_number'] // columns that can be updated
        );
        return self::getBySubs($platformId, $subs);
    }

    public static function getBySub(int $platformId, string $sub): ?self
    {
        return self::where('sub', $sub)
                     ->where('platform_id', $platformId)
                     ->first();
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
        array $claims
    ): LtiRealUser {
        $info = [];
        if (isset($claims[Param::NAME])) $info['name'] = $claims[Param::NAME];
        if (isset($claims[Param::EMAIL])) $info['email'] =$claims[Param::EMAIL];
        if (isset($claims[Param::LIS_URI]) &&
            isset($claims[Param::LIS_URI][Param::PERSON_SOURCEDID])
        ) {
            $info['student_number'] =
                $claims[Param::LIS_URI][Param::PERSON_SOURCEDID];
        }
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
