<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use App\Models\Deployment;
use App\Models\LtiSession;
use App\Models\LtiRealUser;
use App\Models\LtiFakeUser;
use App\Models\Nrps;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class MemberFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Member Filter';

    public function filter(array $params, Nrps $nrps): array
    {
        if (!isset($params[Param::MEMBERS])) {
            $this->ltiLog->debug('Skipping', $nrps);
            return $params;
        }
        $this->ltiLog->debug('Trying', $nrps);
        $toolId = $nrps->tool_id;
        $deployment = Deployment::find($nrps->deployment_id);
        $platformId = $deployment->platform_id;
        $members = $params[Param::MEMBERS];

        // note that the spec says user_id is the same as the oauth sub param
        // we want to map the actual user info by this user_id for easier access
        $membersByIds = [];
        foreach ($members as $member) {
            if (!isset($member[Param::USER_ID]) ||
                !isset($member[Param::ROLES])) {
                // user_id and roles are required by spec, skip if missing
                // TODO: maybe should throw exception instead?
                $this->ltiLog->warning('Entry missing user id or role: ' .
                    json_encode($member), $nrps);
            }
            else {
                $membersByIds[$member[Param::USER_ID]] = $member;
            }
        }

        $realUsers = LtiRealUser::upsertFromNrps($platformId, $members);
        $fakeUsers = LtiFakeUser::getByRealUsers($nrps->course_context_id,
                                                 $toolId, $realUsers);

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
                Param::USER_ID => $fakeUser->sub,
                Param::NAME => $fakeUser->name,
                Param::GIVEN_NAME => $fakeUser->first_name,
                Param::FAMILY_NAME => $fakeUser->last_name,
                Param::EMAIL => $fakeUser->email,
                Param::ROLES => $passthrough[Param::ROLES]
            ];
            // optional params
            if (isset($passthrough[Param::STATUS])) {
                $fakeMember[Param::STATUS] = $passthrough[Param::STATUS];
            }
            $fakeMembers[] = $fakeMember;
            $this->ltiLog->info('Status: ' . $passthrough[Param::STATUS] .
                ' Role: ' . json_encode($passthrough[Param::ROLES]),
                $nrps, $fakeUser->lti_real_user, $fakeUser
            );
        }
        $params[Param::MEMBERS] = $fakeMembers;

        return $params;
    }
}
