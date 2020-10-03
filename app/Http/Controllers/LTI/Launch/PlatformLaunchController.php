<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\Launch\PlatformLaunch;

class PlatformLaunchController extends Controller
{
    /**
     * Send out LTI launch request's first stage, OIDC login
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $response = [];
        try {
            $launch = new PlatformLaunch($request);
            $response = $launch->getloginParams();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return view('lti/launch/platform/send_login', $response);
    }

    /**
     * LTI launch request's second stage, receive the authorization request
     *
     * @param Request $request
     */
    public function auth(Request $request)
    {
        $response = [];
        try {
            $launch = new PlatformLaunch($request);
            $launch->checkAuthRequest();
            $response = $launch->getAuthResponse();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
        return view('lti/launch/platform/send_token', $response);
    }
}

