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
    public function config()
    {
        $platform = Platform::find(config('lti.own_platform_id'));
        $platform->convertToPublicKeys();
        $tool = Tool::find(config('lti.own_tool_id'));
        $tool->convertToPublicKeys();
        $resp = ['platform' => $platform, 'tool' => $tool];
        return $resp;
    }

}
