<?php

namespace App\Http\Controllers\LTI\Launch;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\CourseContext;
use App\Models\LtiFakeUser;
use App\Models\Tool;

class MidwayApiController extends Controller
{
    /**
     * Returns configuration information for the shim.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLtiFakeUsers(
        Request $request,
        CourseContext $courseContext,
        Tool $tool
    ) {

        // make sure the token we got is allowed to access the given course
        // context and tool
        $user = $request->user();
        if (!$user->tokenCan(
            $user->getLookupAbility($courseContext->id, $tool->id))) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        // return the users for this course context/tool pair
        $users = LtiFakeUser::getByCourseContext($courseContext->id, $tool->id);

        // hide some fields we send out
        $users->makeHidden(['login_hint', 'sub', 'lti_real_user_id', 'tool_id',
            'course_context_id', 'created_at', 'updated_at']);
        foreach ($users as $user) {
            $user->lti_real_user->makeHidden(['login_hint', 'email', 'sub',
                'non_lti_id', 'platform_id', 'created_at', 'updated_at']);
        }

        return $users;
    }
}
