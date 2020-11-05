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
     * Handles handing out OAuth tokens for LTI service authentication
     *
     * @param Request $request
     *
     */
    public function getLineitems(Request $request, Ags $ags)
    {
        $platformAgs = new PlatformAgs($request, $ags);
        try {
            return $platformAgs->getLineitems();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function getLineitem(
        Request $request,
        Ags $ags,
        AgsLineitem $lineitem
    ) {
        $platformAgs = new PlatformAgs($request, $ags);
        try {
            return $platformAgs->getLineitem($lineitem);
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}

