<?php
namespace UBC\LTI\Specs\Nrps;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Build;

use App\Models\Nrps;
use App\Models\Tool;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\Security\AccessToken;

use GuzzleHttp\Client;

class ToolNrps
{
    private LtiLog $ltiLog;
    private Request $request;
    private Nrps $nrps;

    public function __construct(Request $request, Nrps $nrps, LtiLog $ltiLog)
    {
        $this->request = $request;
        $this->nrps = $nrps;
        $this->ltiLog = new LtiLog('NRPS (Tool)', $ltiLog->getStreamId());
    }

    public function getNrps(): array
    {
        $this->ltiLog->debug('Requesting access token', $this->request,
                             $this->nrps);
        $accessToken = AccessToken::request(
            $this->nrps->deployment->platform,
            $this->nrps->tool,
            [Param::NRPS_SCOPE_URI]
        );
        $this->ltiLog->debug("Access token: $accessToken", $this->request,
                             $this->nrps);

        $req = Http::withHeaders([
            'Accept' =>
                'application/vnd.ims.lti-nrps.v2.membershipcontainer+json',
            'Authorization' => 'Bearer ' . $accessToken
        ]);
        // the spec allow the 'limit' and 'role' GET params for pagination and
        // filtering purposes, we should be able to pass through those as is
        $queries = [];
        if ($this->request->input(Param::LIMIT)) {
            $queries[Param::LIMIT] = $this->request->input(Param::LIMIT);
        }
        if ($this->request->input(Param::ROLE)) {
            $queries[Param::ROLE] = $this->request->input(Param::ROLE);
        }
        $this->ltiLog->debug('Using queries: ' . json_encode($queries),
            $this->request, $this->nrps);
        $this->ltiLog->debug('Requesting NRPS from target platform');
        $resp = $req->get($this->nrps->getContextMembershipsUrl($queries));

        if ($resp->serverError()) {
            throw new LtiException($this->ltiLog->msg('NRPS platform error: ' .
                $resp->status() . ' ' . $resp->body(),
                $this->request, $this-nrps
            ));
        }
        if ($resp->clientError()) {
            throw new LtiException($this->ltiLog->msg('NRPS client error: ' .
                $resp->status() . ' ' . $resp->body(),
                $this->request, $this->nrps
            ));
        }

        // pagination URLs, if they exist, are in the header
        $link = $resp->header(Param::LINK);

        $ret = $resp->json();
        if ($link) $ret[Param::LINK] = $link;

        $this->ltiLog->debug('Target platform NRPS response: ' .
            json_encode($ret), $this->request, $this->nrps);

        return $ret;
    }
}
