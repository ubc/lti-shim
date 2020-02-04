<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\Launch\ToolLaunch;

class ToolLaunchController extends Controller
{
    /**
     * Handles LTI launch request's first stage, OIDC login
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $response = [];
        $toolLaunch = new ToolLaunch($request);
        try {
            $toolLaunch->checkLogin();
            $response = $toolLaunch->getLoginResponse();
        } catch (LTIException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return view('lti/launch/tool/login_response',
            ['login' => $request->all(), 'response' => $response]);
    }

    /**
     * Handles LTI launch request's third stage, authentication response
     *
     * @param Request $request
     *
     */
    public function auth(Request $request)
    {
        $toolLaunch = new ToolLaunch($request);
        try {
            $toolLaunch->checkAuth();
        } catch (LTIException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
        return redirect()->action('LTI\Launch\PlatformLaunchController@login');
    }
}

