<?php
namespace UBC\LTI\Specs\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Symfony\Component\HttpFoundation\Response as HttpResp;

use Lmc\HttpConstants\Header;

use App\Models\Ags;
use App\Models\AgsLineitem;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Ags\ToolResult;
use UBC\LTI\Specs\Security\AccessToken;

/**
 * Implements AGS result service.
 */
class PlatformResult
{
    private AccessToken $tokenHelper;
    private Ags $ags;
    private LtiLog $ltiLog;
    private Request $request;
    private array $filters;

    public function __construct(Request $request, Ags $ags)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS Result (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
        ];
    }

    public function getResults(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS get result received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);

        // there's only one scope for the result service
        if (!$this->ags->canReadOnlyResult()) {
                throw new LtiException(
                    $this->ltiLog->msg("No scope found for result service"));
        }
        $this->tokenHelper->verify(
            AccessToken::fromRequestHeader($this->request, $this->ltiLog),
            $this->ags->tool,
            [Param::AGS_SCOPE_RESULT_READONLY_URI]
        );

        // access token good, proxy the request
        $toolSide = new ToolResult($this->request, $this->ags, $this->ltiLog);
        // check access token and get the Tool side
        $toolResp = $toolSide->getResult($lineitem);

        // apply filters
        $results = $toolResp->json();
        // TODO: re-enable filter
        //$this->applyLineitemsFilters($lineitems);

        // TODO: re-enable pagination
        // pagination not in json body, but in the Link http header, so we need
        // to filter it separately
        //$linkHeader = $toolResp->header(Param::LINK);
        //if ($linkHeader) {
        //    $this->ltiLog->debug("Link pre-filter: $linkHeader", $this->request,
        //                         $this->ags);
        //    $filtered = $this->paginationFilter->filter(
        //        [Param::LINK => $linkHeader], $this->ags);
        //    $linkHeader = $filtered[Param::LINK];
        //    $this->ltiLog->debug("Link post-filter: $linkHeader",
        //                         $this->request, $this->ags);
        //}

        // create the response to send back to the tool
        $resp = response($results);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_RESULTS);
        // TODO: pagination
        //if ($linkHeader) $resp->header(Param::LINK, $linkHeader);
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
}
