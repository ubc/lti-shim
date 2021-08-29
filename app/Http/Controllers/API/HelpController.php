<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Models\Platform;
use App\Models\Tool;

class HelpController extends Controller
{
    /**
     * Returns configuration information for the shim.
     *
     * @return \Illuminate\Http\Response
     */
    public function config(Platform $platform, Tool $tool)
    {
        $resp = [
            'platform' => [
                'iss' => config('lti.iss'),
                'authUrl' => route('lti.launch.auth'),
                'jwksUrl' => route('lti.jwks.tool'),
                'tokenUrl' => route('lti.security.token'),
            ],
            'tool' => [
                'loginUrl' => $tool->shim_login_url,
                'redirectUrl' => route('lti.launch.redirect'),
                'targetLinkUrl' => $tool->shim_target_link_uri,
                'jwksUrl' => route('lti.jwks.tool'),
            ]
        ];
        return $resp;
    }

}
