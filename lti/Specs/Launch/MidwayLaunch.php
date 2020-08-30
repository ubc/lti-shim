<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\LtiFakeUser;
use App\Models\LtiSession;

use UBC\LTI\Param;

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

        return response()->view('lti/launch/midway', $response);
    }
}
