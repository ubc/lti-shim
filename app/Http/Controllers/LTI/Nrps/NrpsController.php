<?php

namespace App\Http\Controllers\LTI\Nrps;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Controllers\Controller;
use App\Models\Nrps;

use UBC\LTI\LtiException;
use UBC\LTI\Specs\Nrps\PlatformNrps;

class NrpsController extends Controller
{
    /**
     * Handles handing out OAuth tokens for LTI service authentication
     *
     * @param Request $request
     *
     */
    public function nrps(Request $request, Nrps $nrps)
    {
        $platformNrps = new PlatformNrps($request, $nrps);
        try {
            return $platformNrps->getNrps();
        } catch (LtiException $e) {
            report($e);
            abort(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }
}

