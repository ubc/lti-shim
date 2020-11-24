<?php
namespace UBC\LTI\Specs\Ags;

use Faker\Factory as Faker;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

use Lmc\HttpConstants\Header;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\LtiFakeUser;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Security\AccessToken;

class ToolScore
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Request $request;
    private Ags $ags;

    public function __construct(Request $request, Ags $ags, LtiLog $ltiLog)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS Score (Tool)', $ltiLog->getStreamId());
        $this->tokenHelper = new AccessToken($this->ltiLog);
    }

    public function postScore(AgsLineitem $lineitem): Response
    {
        $accessToken = $this->getAccessToken();

        // we need to de-anonymize the score
        $score = $this->request->all();
        if (!isset($score[Param::AGS_USER_ID])) {
            throw new LtiException($this->ltiLog->msg('Missing userId: ' .
                json_encode($score), $this->request, $this->ags, $lineitem));
        }
        $fakeUser = LtiFakeUser::getBySub(
            $this->ags->course_context_id,
            $this->ags->tool_id,
            $score[Param::AGS_USER_ID]
        );
        if (!$fakeUser) {
            throw new LtiException($this->ltiLog->msg('Unknown fake userId: ' .
                json_encode($score), $this->request, $this->ags, $lineitem));
        }
        $score[Param::AGS_USER_ID] = $fakeUser->lti_real_user->sub;

        // fill in the headers we want to send
        $req = Http::withHeaders([
            Header::ACCEPT => [Param::AGS_MEDIA_TYPE_SCORE],
            Header::CONTENT_TYPE => Param::AGS_MEDIA_TYPE_SCORE,
            Header::AUTHORIZATION => Param::BEARER_PREFIX . $accessToken
        ]);

        $resp = $req->post($lineitem->lineitem_scores, $score);
        $this->checkResponseErrors($resp);

        return $resp;
    }

    /**
     * Pass on errors in the response via LtiException if any was found.
     */
    private function checkResponseErrors(Response $resp)
    {
        if ($resp->serverError()) {
            throw new LtiException($this->ltiLog->msg('AGS platform error: ' .
                $resp->status() . ' ' . $resp->body(),
                $this->request, $this->ags
            ));
        }
        if ($resp->clientError()) {
            throw new LtiException($this->ltiLog->msg('AGS client error: ' .
                $resp->status() . ' ' . $resp->body(),
                $this->request, $this->ags
            ));
        }
    }

    private function getAccessToken(): string
    {
        // first need to get the access token
        $this->ltiLog->debug('Requesting access token', $this->request,
                             $this->ags);

        // only one scope for score and it should've been checked by the
        // platform side already
        $accessToken = $this->tokenHelper->request(
            $this->ags->deployment->platform,
            $this->ags->tool,
            [Param::AGS_SCOPE_SCORE_URI]
        );
        $this->ltiLog->debug("Access token: $accessToken", $this->request,
                             $this->ags);
        return $accessToken;
    }
}
