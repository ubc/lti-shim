<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use App\Models\Deployment;
use App\Models\LtiSession;
use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;

use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

class MemberFilter implements FilterInterface
{
    public function filter(array $params, int $deploymentId, int $toolId): array
    {
        if (!isset($params[Param::MEMBERS])) return $params;
        $deployment = Deployment::find($deploymentId);
        $platformId = $deployment->platform_id;
        $members = $params[Param::MEMBERS];

        // note that the spec says user_id is the same as the oauth sub param
        // we want to map the actual user info by this user_id for easier access
        $membersByIds = [];
        foreach ($members as $member) {
            if (!isset($member[Param::USER_ID]) ||
                !isset($member[Param::ROLES])) {
                // user_id and roles are required by spec, skip if missing 
                Log::warn('NRPS member does not have a required field:');
                Log::warn($member);
            }
            else {
                $membersByIds[$member[Param::USER_ID]] = $member;
            }
        }

        $realUsers = $this->getRealUsers($platformId, $toolId, $membersByIds);
        $fakeUsers = LtiFakeUser::getByRealUsers($toolId, $realUsers);

        // some info can be passed straight through, I want to index them
        // by lti_real_users.id for easier lookup
        $passthroughs = [];
        foreach ($realUsers as $realUser) {
            $member = $membersByIds[$realUser->sub];
            $newPassthrough = [
                Param::ROLES => $member[Param::ROLES],
            ];
            // status is an optional param
            if (isset($member[Param::STATUS])) {
                $newPassthrough[Param::STATUS] = $member[Param::STATUS];
            }
            $passthroughs[$realUser->id] = $newPassthrough;
        }
        // create the fake members to return
        $fakeMembers = [];
        foreach ($fakeUsers as $fakeUser) {
            $passthrough = $passthroughs[$fakeUser->lti_real_user_id];
            $fakeMember = [
                Param::USER_ID => $fakeUser->login_hint,
                Param::NAME => $fakeUser->name,
                Param::EMAIL => $fakeUser->email,
                Param::ROLES => $passthrough[Param::ROLES]
            ];
            // optional params
            if (isset($passthrough[Param::STATUS])) {
                $fakeMember[Param::STATUS] = $passthrough[Param::STATUS];
            }
            $fakeMembers[] = $fakeMember;
        }
        $params[Param::MEMBERS] = $fakeMembers;

        return $params;
    }

    // get the real user entries from the database, will create an entry
    // for users that don't exist the database
    private function getRealUsers(
        int $platformId,
        int $toolId,
        array $usersBySub
    ): Collection {
        // get users that already exist in the database
        $userSubs = array_keys($usersBySub);
        $existingUsers = LtiRealUser::getBySubs($platformId, $userSubs);
        // this feels messy, but there's going to be users who aren't in the
        // database, so we need to find out who those non-existent users are
        // and enter them into the database
        $existingUserSubs = [];
        foreach ($existingUsers as $user) $existingUserSubs[] = $user->sub;
        $newUserSubs = array_diff($userSubs, $existingUserSubs);
        // create users that aren't in the database, the difficulty here is
        // that we want to avoid creating the users one by one, cause that
        // would generate a lot of sql queries. The problem is that Eloquent
        // doesn't have bulk create, so we have to use the lower level database
        // methods that operate on arrays.
        $newUsersInfo = [];
        foreach ($newUserSubs as $newUserSub) {
            $newUsersInfo[] = $usersBySub[$newUserSub];
        }
        $newUsers = LtiRealUser::createFromNRPS($platformId, $toolId,
                                                    $newUsersInfo);
        return $existingUsers->merge($newUsers);
    }
}
