<?php

namespace App\Http\Controllers\LTI\Launch;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\DeepLink\LoginHandler;

class DeepLinkController extends Controller
{
    /**
     * Send out LTI launch request's first stage, OIDC login
     *
     * @param Request $request
     */
    public function login(Request $request)
    {
        $handler = new LoginHandler($request);
        return $handler->sendLogin();
    }

}

