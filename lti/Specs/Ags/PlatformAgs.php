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
use UBC\LTI\Specs\Ags\Filters\LineitemsFilter;
use UBC\LTI\Specs\Ags\Filters\PaginationFilter;
use UBC\LTI\Specs\Security\AccessToken;

class PlatformAgs
{
    private AccessToken $tokenHelper;
    private Ags $ags;
    private LtiLog $ltiLog;
    private PaginationFilter $paginationFilter;
    private Request $request;
    private array $filters;

    public function __construct(Request $request, Ags $ags)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
            new LineitemsFilter($this->ltiLog)
        ];
        // pagination filter is called separately so it's not in $filters
        $this->paginationFilter = new PaginationFilter($this->ltiLog);
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
        $toolResp = $toolAgs->getLineitems();

        // apply filters
        $lineitems = $toolResp->json();
        $this->ltiLog->debug('Pre-filter: ' . json_encode($lineitems),
            $this->request, $this->ags);
        foreach ($this->filters as $filter) {
            $lineitems = $filter->filter($lineitems, $this->ags);
        }
        $this->ltiLog->debug('Post-filter: ' . json_encode($lineitems),
            $this->request, $this->ags);

        // pagination not in json body, but in the Link http header, so we need
        // to filter it separately
        $linkHeader = $toolResp->header(Param::LINK);
        if ($linkHeader) {
            $this->ltiLog->debug("Link pre-filter: $linkHeader", $this->request,
                                 $this->ags);
            $filtered = $this->paginationFilter->filter(
                [Param::LINK => $linkHeader], $this->ags);
            $linkHeader = $filtered[Param::LINK];
            $this->ltiLog->debug("Link post-filter: $linkHeader",
                                 $this->request, $this->ags);
        }

        // create the response to send back to the tool
        $resp = response($lineitems);
        if ($linkHeader) $resp->header(Param::LINK, $linkHeader);
        return $resp;
    }

    public function getLineitem(Lineitem $lineitem): Response
    {
        // TODO implement
        $response = response('"blah"');
        return $response;
    }
}
