<?php
namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LtiFakeUser extends Model
{
    protected $fillable = [
        'email',
        'login_hint',
        'lti_real_user_id',
        'name',
        'tool_id'
    ];
    protected $with = ['lti_real_user'];

    private static $faker;

    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }

    public function lti_real_user()
    {
        return $this->belongsTo('App\Models\LtiRealUser');
    }

    public static function getByCourseContext(
        int $courseContextId,
        int $toolId
    ): Collection {
        return self::where('course_context_id', $courseContextId)
                     ->where('tool_id', $toolId)
                     ->get();

    }

    public static function getByRealUser(
        int $courseContextId,
        int $toolId,
        LtiRealUser $realUser
    ): self {
        $fakeUsers = self::getByRealUsers($courseContextId, $toolId,
                                          collect([$realUser]));
        return $fakeUsers->first();
    }

    // Get the fake users associated with the given tool based on the given list
    // of real users. Will create a fake user if they don't exist already.
    public static function getByRealUsers(
        int $courseContextId,
        int $toolId,
        Collection $realUsers
    ): Collection {
        // map real users by their id
        $realUserIds = [];
        foreach ($realUsers as $realUser) {
            $realUserIds[] = $realUser->id;
        }
        // get existing users
        $existingUsers = self::getByRealUserIds($courseContextId, $toolId,
                                                $realUserIds);
        // figure out which real users needs a new fake user
        $existingUserIds = [];
        foreach ($existingUsers as $user)
            $existingUserIds[] = $user->lti_real_user_id;
        $newUserIds = array_diff($realUserIds, $existingUserIds);
        // create the missing users, if there are any
        $newUsers = collect([]);
        if (!empty($newUserIds)) {
            $newUsersInfo = [];
            $faker = self::faker();
            foreach ($newUserIds as $newUserId) {
                $userInfo = [
                    'lti_real_user_id' => $newUserId,
                    'course_context_id' => $courseContextId,
                    'tool_id' => $toolId,
                    'login_hint' => $faker->uuid,
                    'sub' => $faker->uuid,
                    'name' => $faker->name,
                    'email' => $faker->email,
                    'student_number' => $faker->ean13
                ];
                $newUsersInfo[] = $userInfo;
            }
            self::insert($newUsersInfo);
            // can't find an easy way to get the bulk inserted rows back,
            // so have to do another query to get the new rows
            $newUsers = self::getByRealUserIds($courseContextId, $toolId,
                                               $newUserIds);
        }

        return $existingUsers->merge($newUsers);
    }

    public static function getByRealUserIds(
        int $courseContextId,
        int $toolId,
        array $realUserIds
    ): Collection {
        return self::whereIn('lti_real_user_id', $realUserIds)
                     ->where('course_context_id', $courseContextId)
                     ->where('tool_id', $toolId)
                     ->get();
    }

    private static function faker()
    {
        if (!isset(self::$faker)) self::$faker = Faker::create();
        return self::$faker;
    }
}
