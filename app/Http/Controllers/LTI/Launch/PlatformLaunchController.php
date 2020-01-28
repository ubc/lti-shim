<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use UBC\LTI\LTIException;
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
        $launch = new PlatformLaunch($request);
        try {
            $response = $launch->getloginParams();
        } catch (LTIException $e) {
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return view('lti/launch/platform/send_login', ['response' => $response]);
    }
}

