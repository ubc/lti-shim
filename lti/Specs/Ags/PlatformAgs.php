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
        // check access token and get the Tool side
        $toolAgs = $this->getToolAgs();
        // proxy the request
        $toolResp = $toolAgs->getLineitems();

        // apply filters
        $lineitems = $toolResp->json();
        $this->applyLineitemsFilters($lineitems);

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

    public function getLineitem(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS request for lineitem received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        // check access token and get the Tool side
        $toolAgs = $this->getToolAgs();
        // proxy the request
        $toolResp = $toolAgs->getLineitem($lineitem);
        $lineitems = [ $toolResp->json() ];
        $this->applyLineitemsFilters($lineitems);

        $response = response($lineitems[0]);
        return $response;
    }

    private function applyLineitemsFilters(array &$lineitems)
    {
        $this->ltiLog->debug('Pre-filter: ' . json_encode($lineitems),
            $this->request, $this->ags);
        foreach ($this->filters as $filter) {
            $lineitems = $filter->filter($lineitems, $this->ags);
        }
        $this->ltiLog->debug('Post-filter: ' . json_encode($lineitems),
            $this->request, $this->ags);
    }

    private function getToolAgs(): ToolAgs
    {
        // TODO: check that token scope can carry out specified operation
        $this->tokenHelper->verify(AccessToken::fromRequestHeader(
            $this->request, $this->ltiLog));

        // access token good, proxy the request
        $toolAgs = new ToolAgs($this->request, $this->ags, $this->ltiLog);

        return $toolAgs;
    }
}
