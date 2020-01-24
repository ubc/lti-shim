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
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return view('lti/launch/login_response',
            ['login' => $request->all(), 'response' => $response]);
    }
}

