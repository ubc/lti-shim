<?php

namespace App\Http\Controllers\LTI\Core;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;

use App\Models\ReturnUrl;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\Core\PlatformReturnUrl;

/**
 * The launch presentation claim allows a return_url parameter that tells the
 * tool how to return the platform. This controller handles the shim platform
 * side of things and should redirect the user back to the actual return url.
 */
class ReturnUrlController extends Controller
{
    /**
     * Handles a get request to a return url endpoint on the shim platform.
     *
     * @param Request $request
     * @param ReturnUrl $returnUrl - the ReturnUrl entry in the database
     * @param string $token - basic security check, since we don't want people
     *  to just increment ids to find out all the original return urls in the
     *  shim
     */
    public function getReturnUrl(
        Request $request,
        ReturnUrl $returnUrl,
        string $token
    ) {
        $platformReturnUrl = new PlatformReturnUrl($request, $returnUrl);
        return $platformReturnUrl->getReturnUrl($token);
    }

}

