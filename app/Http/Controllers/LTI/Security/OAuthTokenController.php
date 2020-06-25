<?php

namespace App\Http\Controllers\LTI\Security;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use UBC\LTI\LTIException;
use UBC\LTI\Specs\Security\PlatformOAuthToken;

class OAuthTokenController extends Controller
{
    /**
     * Handles handing out OAuth tokens for LTI service authentication
     *
     * @param Request $request
     *
     */
    public function platformToken(Request $request)
    {
        $oauthToken = new PlatformOAuthToken($request);
        try {
            return $oauthToken->processTokenRequest();
        } catch (LTIException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}

