<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\DeepLink;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\DeepLink\LoginHandler;
use UBC\LTI\Specs\DeepLink\AuthReqHandler;
use UBC\LTI\Specs\DeepLink\AuthRespHandler;
use UBC\LTI\Specs\DeepLink\ReturnHandler;

class DeepLinkController extends Controller
{
    /**
     * Receive LTI launch request's first stage, OIDC login, from a platform.
     * Returns an OIDC login to target tool.
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $handler = new LoginHandler($request);
        return $handler->sendLogin();
    }

    /**
     * Receive LTI launch request's second stage, auth request, from a tool.
     * Returns an auth request to originating platform.
     *
     * @param Request $request
     */
    public function auth(Request $request)
    {
        $handler = new AuthReqHandler($request);
        return $handler->sendAuth();
    }

    /**
     * Receive LTI launch request's third stage, auth response, from a platform.
     * Returns an auth response to the target tool.
     *
     * @param Request $request
     */
    public function redirect(Request $request)
    {
        $handler = new AuthRespHandler($request);
        return $handler->sendAuth();
    }

    /**
     * Receive LTI deep linking's last stage (adds a 4th stage to lti launch)
     * from a tool. Returns the deep link return to the originating platform
     *
     * @param Request $request
     */
    public function return(Request $request, DeepLink $deepLink)
    {
        $handler = new ReturnHandler($request);
        return $handler->sendReturn();
    }
}

