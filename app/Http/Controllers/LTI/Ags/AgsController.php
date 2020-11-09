<?php

namespace App\Http\Controllers\LTI\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;
use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Specs\Ags\PlatformAgs;

class AgsController extends Controller
{
    /**
     * GET lineitems, return a list of grade lineitems
     */
    public function getLineitems(Request $request, Ags $ags)
    {
        $platformAgs = new PlatformAgs($request, $ags);
        return $platformAgs->getLineitems();
    }

    /**
     * POST lineitems, create a new lineitem entry
     */
    public function postLineitems(Request $request, Ags $ags)
    {
        $platformAgs = new PlatformAgs($request, $ags);
        return $platformAgs->postLineitems();
    }

    public function getLineitem(
        Request $request,
        Ags $ags,
        AgsLineitem $lineitem
    ) {
        $platformAgs = new PlatformAgs($request, $ags);
        return $platformAgs->getLineitem($lineitem);
    }
}

