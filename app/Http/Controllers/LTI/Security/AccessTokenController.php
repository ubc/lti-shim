<?php

namespace App\Http\Controllers\LTI\Security;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use UBC\LTI\LtiException;
use UBC\LTI\Specs\Security\PlatformAccessToken;

class AccessTokenController extends Controller
{
    /**
     * Handles handing out OAuth access tokens for LTI service authentication
     *
     * @param Request $request
     *
     */
    public function platformToken(Request $request)
    {
        $oauthToken = new PlatformAccessToken($request);
        try {
            return $oauthToken->processTokenRequest();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}

