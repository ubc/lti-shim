<?php
namespace UBC\LTI\Specs\Ags;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Symfony\Component\HttpFoundation\Response as HttpResp;

use Lmc\HttpConstants\Header;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\AgsResult;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Ags\ToolResult;
use UBC\LTI\Specs\Ags\Filters\ResultsFilter;
use UBC\LTI\Specs\Ags\Filters\ResultPaginationFilter;
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
    private ResultPaginationFilter $paginationFilter;
    private array $filters;

    public function __construct(Request $request, Ags $ags)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS Result (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
            new ResultsFilter($this->ltiLog)
        ];
        $this->paginationFilter = new ResultPaginationFilter($this->ltiLog);
    }

    public function getResults(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS get results received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        $this->checkToken();

        // access token good, proxy the request
        $toolSide = new ToolResult($this->request, $this->ags, $this->ltiLog);
        // check access token and get the Tool side
        $toolResp = $toolSide->getResults($lineitem);

        // apply filters
        $results = $toolResp->json();
        $this->applyFilters($results, $lineitem);

        // pagination not in json body, but in the Link http header, so we need
        // to filter it separately
        $linkHeader = $toolResp->header(Param::LINK);
        if ($linkHeader) {
            $this->ltiLog->debug("Link pre-filter: $linkHeader", $this->request,
                                 $this->ags, $lineitem);
            $filtered = $this->paginationFilter->filter(
                [Param::LINK => $linkHeader], $this->ags, $lineitem);
            $linkHeader = $filtered[Param::LINK];
            $this->ltiLog->debug("Link post-filter: $linkHeader",
                                 $this->request, $this->ags, $lineitem);
        }

        // create the response to send back to the tool
        $resp = response($results);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_RESULTS);
        if ($linkHeader) $resp->header(Param::LINK, $linkHeader);

        $this->ltiLog->notice('AGS get result completed', $this->request,
            $this->ags, $lineitem);
        return $resp;
    }

    public function getResult(
        AgsLineitem $lineitem,
        AgsResult $result
    ): Response {
        $this->ltiLog->info('AGS get result received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        $this->checkToken();

        // access token good, proxy the request
        $toolSide = new ToolResult($this->request, $this->ags, $this->ltiLog);
        // check access token and get the Tool side
        $toolResp = $toolSide->getResult($lineitem, $result);

        $results = $toolResp->json();

        // Undefined behaviour in the specs. Spec does not define what happens
        // if we're retrieving a single result. When retrieving a specific
        // result, Canvas will give us just that result. However, the spec only
        // defines one media type for results and that is an array of results.
        // It's easier for me to just return everything as an array of results
        // since pagination links use the same url, so that's what we're going
        // with.
        if ($this->isJsonObject($toolResp->body())) {
            $this->ltiLog->debug('NOT AN ARRAY');
            $results = [$results];
        }
        $this->applyFilters($results, $lineitem);

        // create the response to send back to the tool
        $resp = response($results);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_RESULTS);

        $this->ltiLog->notice('AGS get result completed', $this->request,
            $this->ags, $lineitem);
        return $resp;
    }

    private function applyFilters(array &$results, AgsLineitem $lineitem)
    {
        $this->ltiLog->debug('Pre-filter: ' . json_encode($results),
            $this->request, $this->ags);
        foreach ($this->filters as $filter) {
            $results = $filter->filter($results, $this->ags, $lineitem);
        }
        $this->ltiLog->debug('Post-filter: ' . json_encode($results),
            $this->request, $this->ags);
    }

    private function checkToken()
    {
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
    }

    /**
     * Returns true if the raw string is a json object.
     */
    private function isJsonObject(string $raw): bool
    {
        $raw = ltrim($raw);
        if (!$raw) return false;
        if ($raw[0] == '{') return true;
        return false;
    }
}
