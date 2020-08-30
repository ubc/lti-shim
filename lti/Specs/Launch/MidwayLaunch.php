<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\LtiFakeUser;
use App\Models\LtiSession;

use UBC\LTI\Param;
use UBC\LTI\Specs\RoleVocabulary;

class MidwayLaunch
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getArrivalResponse(): Response
    {
        $ltiSession = LtiSession::getSession($this->request);

        $users = LtiFakeUser::getByCourseContext(
            $ltiSession->course_context_id,
            $ltiSession->tool_id
        );

        $response = [
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT),
            'users' => $users
        ];

        $roleVo = new RoleVocabulary();
        if ($roleVo->canLookupRealUsers($ltiSession->token[Param::ROLES_URI])) {
            return response()->view('lti/launch/midway/lookup', $response);
        }
        else {
            return response()->view('lti/launch/midway/auto', $response);
        }
    }
}
