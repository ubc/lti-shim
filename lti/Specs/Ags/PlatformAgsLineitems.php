<?php
namespace UBC\LTI\Specs\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Lmc\HttpConstants\Header;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Ags\ToolAgs;
use UBC\LTI\Specs\Ags\Filters\LineitemsFilter;
use UBC\LTI\Specs\Ags\Filters\PaginationFilter;
use UBC\LTI\Specs\Security\AccessToken;

class PlatformAgsLineitems
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
        $this->ltiLog = new LtiLog('AGS Lineitems (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
            new LineitemsFilter($this->ltiLog)
        ];
        // pagination filter is called separately so it's not in $filters
        $this->paginationFilter = new PaginationFilter($this->ltiLog);
    }

    // ------ Lineitems Operations: GET/POST ------

    public function getLineitems(): Response
    {
        $this->ltiLog->info('AGS get lineitems received at ' .
            $this->request->fullUrl(), $this->request, $this->ags);
        // check access token and get the Tool side
        $toolAgs = $this->getToolAgs(true);
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
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_LINEITEMS);
        if ($linkHeader) $resp->header(Param::LINK, $linkHeader);
        return $resp;
    }

    /**
     * POST a single lineitem to the lineitems url to create that lineitem in
     * the platform.
     */
    public function postLineitems(): Response
    {
        $this->ltiLog->info('AGS create lineitem received at ' .
            $this->request->fullUrl(), $this->request, $this->ags);
        // check access token and get the Tool side
        $toolAgs = $this->getToolAgs(false);
        // proxy the request
        $toolResp = $toolAgs->postLineitems();
        $lineitems = [ $toolResp->json() ];
        $this->applyLineitemsFilters($lineitems);

        $resp = response($lineitems[0]);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_LINEITEM);
        return $resp;
    }

    // ------ Lineitem Operations: GET/PUT/DELETE ------

    /**
     * GET information on a single lineitem
     */
    public function getLineitem(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS get lineitem received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        // check access token and get the Tool side
        $toolAgs = $this->getToolAgs(true);
        // proxy the request
        $toolResp = $toolAgs->getLineitem($lineitem);
        $lineitems = [ $toolResp->json() ];
        $this->applyLineitemsFilters($lineitems);

        $resp = response($lineitems[0]);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_LINEITEM);
        return $resp;
    }

    /**
     * PUT request to the lineitem url lets you modify the lineitem.
     */
    public function putLineitem(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS put lineitem received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        $resp = response(json_encode('Edit lineitem not implemented'));
        return $resp;
    }

    /**
     * DELETE request to the lineitem url lets you delete the lineitem.
     */
    public function deleteLineitem(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS delete lineitem received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        $resp = response(json_encode('Delete lineitem not implemented'));
        return $resp;
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

    private function getToolAgs(bool $isReadOnly): ToolAgs
    {
        $scopes = $this->ags->getLineitemScopes($isReadOnly);
        if (!$scopes) {
                throw new LtiException(
                    $this->ltiLog->msg("No scopes available for operation"));
        }
        $this->ltiLog->debug('Using scope: ' . json_encode($scopes),
            $this->request, $this->ags);

        $this->tokenHelper->verify(
            AccessToken::fromRequestHeader($this->request, $this->ltiLog),
            $this->ags->tool,
            $scopes
        );

        // access token good, proxy the request
        $toolAgs = new ToolAgs($this->request, $this->ags, $this->ltiLog);

        return $toolAgs;
    }
}
