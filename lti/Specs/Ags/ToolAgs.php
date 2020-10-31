<?php
namespace UBC\LTI\Specs\Ags;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;

use Jose\Easy\Build;

use App\Models\Ags;
use App\Models\Tool;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Security\AccessToken;

class ToolAgs
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Request $request;
    private Ags $ags;

    public function __construct(Request $request, Ags $ags, LtiLog $ltiLog)
    {
        $this->request = $request;
        $this->ags = $ags;
        $this->ltiLog = new LtiLog('AGS (Tool)', $ltiLog->getStreamId());
        $this->tokenHelper = new AccessToken($this->ltiLog);
    }

    public function getLineitems(): Response
    {
        $this->ltiLog->debug('Requesting access token', $this->request,
                             $this->ags);
        // TODO need to handle single lineitem case when Deep Linking is added
        // TODO need to change token scope depending on actual available scopes
        // TODO handle params
        $accessToken = $this->tokenHelper->request(
            $this->ags->deployment->platform,
            $this->ags->tool,
            [Param::AGS_SCOPE_LINEITEM_READONLY_URI]
        );
        $this->ltiLog->debug("Access token: $accessToken", $this->request,
                             $this->ags);

        // TODO lineitem media type for getLineitem()
        $req = Http::withHeaders([
            'Accept' => [
                Param::AGS_MEDIA_TYPE_LINEITEMS
            ],
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $filters = $this->getLineitemFilters();
        $this->ltiLog->debug('Lineitems Filters: ' . json_encode($filters),
                             $this->request, $this->ags);

        $resp = $req->get($this->ags->getLineitemsUrl($filters));

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

        return $resp;
    }

    /**
     * GET requests to the lineitem endpoints can be accompanied by queries
     * used for filtering purposes:
     *
     * * resource_link_id - limit to only items associated with the given
     *      resource link. A resource could be like an assignment.
     * * resource_id - limit to a only items associated with the resource.
     * * tag - limit to only lineitems with the given tag
     * * limit - restrict the number of items returned, note that platforms
     *      may return less than the given limit and pagination is supported
     *      using the same link http header mechanism as NRPS
     */
    private function getLineitemFilters(): array
    {
        $filters = [];
        $this->addQueryIfExists(Param::RESOURCE_LINK_ID, $filters);
        $this->addQueryIfExists(Param::RESOURCE_ID, $filters);
        $this->addQueryIfExists(Param::TAG, $filters);
        $this->addQueryIfExists(Param::LIMIT, $filters);
        return $filters;
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
}
