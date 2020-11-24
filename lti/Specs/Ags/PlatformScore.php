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
use UBC\LTI\Specs\Ags\ToolScore;
use UBC\LTI\Specs\Ags\Filters\ScoreFilter;
use UBC\LTI\Specs\Security\AccessToken;

/**
 * Implements AGS result service.
 */
class PlatformScore
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
        $this->ltiLog = new LtiLog('AGS Score (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->filters = [
            new ScoreFilter($this->ltiLog)
        ];
    }

    public function postScore(AgsLineitem $lineitem): Response
    {
        $this->ltiLog->info('AGS post score received at ' .
            $this->request->fullUrl(), $this->request, $this->ags, $lineitem);
        $this->checkToken();

        // access token good, proxy the request
        $toolSide = new ToolScore($this->request, $this->ags, $this->ltiLog);
        // check access token and get the Tool side
        $toolResp = $toolSide->postScore($lineitem);

        // apply filters
        $scoreResp = $toolResp->json();
        $this->applyFilters($scoreResp, $lineitem);

        // create the response to send back to the tool
        $resp = response($scoreResp);
        $resp->header(Header::CONTENT_TYPE, Param::AGS_MEDIA_TYPE_SCORE);

        $this->ltiLog->notice('AGS post score completed', $this->request,
            $this->ags, $lineitem);
        return $resp;
    }

    private function applyFilters(array &$scoreResp, AgsLineitem $lineitem)
    {
        $this->ltiLog->debug('Pre-filter: ' . json_encode($scoreResp),
            $this->request, $this->ags);
        foreach ($this->filters as $filter) {
            $scoreResp = $filter->filter($scoreResp, $this->ags, $lineitem);
        }
        $this->ltiLog->debug('Post-filter: ' . json_encode($scoreResp),
            $this->request, $this->ags);
    }

    private function checkToken()
    {
        // there's only one scope for the result service
        if (!$this->ags->canWriteScore()) {
                throw new LtiException(
                    $this->ltiLog->msg("No scope found for score service"));
        }
        $this->tokenHelper->verify(
            AccessToken::fromRequestHeader($this->request, $this->ltiLog),
            $this->ags->tool,
            [Param::AGS_SCOPE_SCORE_URI]
        );
    }
}
