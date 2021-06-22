<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\DeepLink;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\Launch\LaunchDirector;
use UBC\LTI\Specs\DeepLink\ReturnHandler;

class LaunchController extends Controller
{
    /**
     * Receive LTI launch request's first stage, OIDC login, from a platform.
     * Returns an OIDC login to target tool.
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $director = new LaunchDirector($request);
        return $director->login();
    }

    /**
     * Receive LTI launch request's second stage, auth request, from a tool.
     * Returns an auth request to originating platform.
     *
     * @param Request $request
     */
    public function auth(Request $request)
    {
        $director = new LaunchDirector($request);
        return $director->authReq();
    }

    /**
     * Receive LTI launch request's third stage, auth response, from a platform.
     * Returns an auth response to the target tool.
     *
     * @param Request $request
     */
    public function redirect(Request $request)
    {
        $director = new LaunchDirector($request);
        return $director->authResp();
    }

    /**
     * Send the user to midway where they can interact with the shim.
     *
     * @param Request $request
     */
    public function midway(Request $request)
    {
        $director = new LaunchDirector($request);
        return $director->midway();
    }

    /**
     * Receive LTI deep linking's last stage (adds a 4th stage to lti launch)
     * from a tool. Returns the deep link return to the originating platform
     *
     * This is technically deep link only, but since deep link adds an
     * additional stage to launch, it makes more sense to have it here in the
     * LaunchController.
     *
     * @param Request $request
     */
    public function return(Request $request, DeepLink $deepLink)
    {
        $handler = new ReturnHandler($request);
        return $handler->sendReturn();
    }
}

