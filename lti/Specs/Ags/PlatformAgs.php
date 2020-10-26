<?php
namespace UBC\LTI\Specs\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Ags\ToolAgs;
use UBC\LTI\Specs\Security\AccessToken;

class PlatformAgs
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Ags $ags;
    private Request $request;
    private array $filters;

    public function __construct(Request $request, Ags $ags)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [];
    }

    public function getLineitems(): Response
    {
        $this->ltiLog->info('AGS request received at ' .
            $this->request->fullUrl(), $this->request, $this->ags);
        $this->tokenHelper->verify(AccessToken::fromRequestHeader(
            $this->request, $this->ltiLog));

        // TODO: handle AGS parameters

        // access token good, proxy the request
        $toolAgs = new ToolAgs($this->request, $this->ags, $this->ltiLog);
        $agsData = $toolAgs->getLineitems();

        $response = response($agsData);
        return $response;
    }

    public function getLineitem(Lineitem $lineitem): Response
    {
        // TODO implement
        $response = response('"blah"');
        return $response;
    }
}
