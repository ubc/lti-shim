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
        $platform = Platform::getOwnPlatform();
        $platform->convertToPublicKeys();
        $tool = Tool::getOwnTool();
        $tool->convertToPublicKeys();
        $resp = ['platform' => $platform, 'tool' => $tool];
        return $resp;
    }

}
