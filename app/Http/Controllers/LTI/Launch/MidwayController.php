<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;
use App\Models\LtiSession;
use App\Models\Tool;

use UBC\LTI\LtiException;
use UBC\LTI\Param;
use UBC\LTI\Specs\Launch\MidwayLaunch;

/**
 * Transfers the user from the shim's tool side to the platform side.
 * If this is a new launch, the user needs to choose which tool to connect.
 * Once the choice has been made, subsequent launches will always go to
 * that tool.
 */
class MidwayController extends Controller
{
    /**
     * Deals with requests coming from the tool side.
     *
     * @param Request $request
     */
    public function arrival(Request $request)
    {
        try {
            $midwayLaunch = new MidwayLaunch($request);
            return $midwayLaunch->getArrivalResponse();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    /**
     * Deals with requests leaving for the platform side.
     *
     * @param Request $request
     */
    public function departure(Request $request)
    {
        Log::channel('lti')->debug("Launch (Midway) From " . $request->ip() .
            " Depart to Platform Side");
        return redirect()->action(
            'LTI\Launch\PlatformLaunchController@login',
            [Param::LTI_MESSAGE_HINT=> $request->input(Param::LTI_MESSAGE_HINT)]
        );
    }
}

