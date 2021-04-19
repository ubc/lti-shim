<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\DeepLink\LoginHandler;
use UBC\LTI\Specs\DeepLink\AuthReqHandler;

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

}

