<?php
namespace UBC\LTI\Specs\Ags;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;

use Lmc\HttpConstants\Header;

use App\Models\Ags;
use App\Models\AgsLineitem;
use App\Models\AgsResult;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Security\AccessToken;

class ToolResult
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Request $request;
    private Ags $ags;

    // true if the user_id filter cannot be matched to a known user in our db
    private bool $hasUnknownUserId = false;

    public function __construct(Request $request, Ags $ags, LtiLog $ltiLog)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS Result (Tool)', $ltiLog->getStreamId());
        $this->tokenHelper = new AccessToken($this->ltiLog);
    }

    public function getResults(AgsLineitem $lineitem): Response
    {
        $accessToken = $this->getAccessToken();
        // fill in the headers we want to send
        $req = Http::withHeaders([
            Header::ACCEPT => [Param::AGS_MEDIA_TYPE_RESULTS],
            Header::AUTHORIZATION => Param::BEARER_PREFIX . $accessToken
        ]);

        $filters = [];
        $this->addUserIdQueryIfExists($filters);
        $this->addQueryIfExists(Param::LIMIT, $filters);
        $this->ltiLog->debug(
            'Results url with filters: ' .
                $lineitem->getLineitemResultsUrl($filters),
            $this->request,
            $this->ags
        );

        if ($this->hasUnknownUserId) {
            // since we don't know who this user is, we can't rewrite the
            // user id, so just return an empty response
            return new Response(new GuzzleResponse(200, [], '[]'));
        }

        $resp = $req->get($lineitem->getLineitemResultsUrl($filters));
        $this->checkResponseErrors($resp);

        return $resp;
    }

    public function getResult(
        AgsLineitem $lineitem,
        AgsResult $result
    ): Response
    {
        $accessToken = $this->getAccessToken();
        // fill in the headers we want to send
        $req = Http::withHeaders([
            Header::ACCEPT => [Param::AGS_MEDIA_TYPE_RESULTS],
            Header::AUTHORIZATION => Param::BEARER_PREFIX . $accessToken
        ]);
        $resp = $req->get($result->result);
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

    /**
     * Check the GET queries sent in the request to see if the given $queryKey
     * exists, if so, put the value into the $target array.
     */
    private function addQueryIfExists(string $queryKey, array &$target)
    {
        if ($this->request->query($queryKey)) {
            $target[$queryKey] = $this->request->query($queryKey);
        }
    }

    /**
     * Since the user_id filter comes from the tool, it'll be a fake user id
     * which we need to replace with the real user id.
     *
     * An edge case we need to watch out for is if we can't find the user. This
     * is what the $hasUnknownUserId is for.
     */
    private function addUserIdQueryIfExists(array &$filters)
    {
        $userId = $this->request->query(Param::USER_ID);
        if (!$userId) return; // no user_id filter
        $fakeUser = LtiFakeUser::getBySub(
            $this->ags->course_context_id,
            $this->ags->tool_id,
            $userId
        );
        if (!$fakeUser) {
            $this->ltiLog->warning('user_id filter has an unknown user: ' .
                $userId, $this->ags);
            $this->hasUnknownUserId = true;
            return;
        }
        $filters[Param::USER_ID] = $fakeUser->lti_real_user->sub;
    }

    private function getAccessToken(): string
    {
        // first need to get the access token
        $this->ltiLog->debug('Requesting access token', $this->request,
                             $this->ags);

        // only one scope for result and it should've been checked by the
        // platform side already
        $accessToken = $this->tokenHelper->request(
            $this->ags->deployment->platform,
            $this->ags->tool,
            [Param::AGS_SCOPE_RESULT_READONLY_URI]
        );
        $this->ltiLog->debug("Access token: $accessToken", $this->request,
                             $this->ags);
        return $accessToken;
    }
}
