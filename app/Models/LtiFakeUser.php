<?php
namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use Ramsey\Uuid\Uuid;

use UBC\LTI\Utils\FakeName;

class LtiFakeUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
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

    public function getFirstNameAttribute(): string
    {
        return explode(FakeName::DELIMITER, $this->name)[0];
    }

    public function getLastNameAttribute(): string
    {
        return explode(FakeName::DELIMITER, $this->name)[1];
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
                $fakeSub = Uuid::uuid4()->toString();
                $fakeName = FakeName::name();
                $fakeEmail = self::generateFakeEmail($fakeSub, $fakeName);
                $userInfo = [
                    'lti_real_user_id' => $newUserId,
                    'course_context_id' => $courseContextId,
                    'tool_id' => $toolId,
                    'sub' => $fakeSub,
                    'name' => $fakeName,
                    'email' => $fakeEmail,
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

    public static function getBySub(
        int $courseContextId,
        int $toolId,
        string $sub
    ): ?self {
        return self::where('sub', $sub)
                     ->where('course_context_id', $courseContextId)
                     ->where('tool_id', $toolId)
                     ->first();
    }

    /**
     * Generate a name based UUID for our email address. This is a determinstic
     * UUID based on the fake user's sub & name. In case we need to recreate
     * the fake email for some reason.
     */
    public static function generateFakeEmail(
        string $sub,
        string $name
    ): string {
        $uuid = Uuid::uuid5($sub, $name)->toString();
        $uuid = str_replace('-', '', $uuid);
        return $uuid . '@' . config('lti.fake_email_domain');
    }

    private static function faker()
    {
        if (!isset(self::$faker)) self::$faker = Faker::create();
        return self::$faker;
    }
}
