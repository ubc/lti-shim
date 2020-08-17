<?php
namespace UBC\LTI\Specs\Nrps;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Build;

use App\Models\Nrps;
use App\Models\Tool;

use UBC\LTI\LTIException;
use UBC\LTI\Param;
use UBC\LTI\Specs\Security\AccessToken;

use GuzzleHttp\Client;

class ToolNrps
{
    private Request $request;
    private Nrps $nrps;

    public function __construct(Request $request, Nrps $nrps)
    {
        $this->request = $request;
        $this->nrps = $nrps;
    }

    public function getNrps(): array
    {
        $accessToken = AccessToken::request(
            $this->nrps->deployment->platform,
            $this->nrps->tool,
            [Param::NRPS_SCOPE_URI]
        );

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
        $resp = $req->get($this->nrps->getContextMembershipsUrl($queries));

        if ($resp->serverError()) {
            throw new LTIException('NRPS platform error: ' . $resp->status()
                . ' ' . $resp->body());
        }
        if ($resp->clientError()) {
            throw new LTIException('NRPS client error: ' . $resp->status() . ' '
                . $resp->body());
        }

        // pagination URLs, if they exist, are in the header
        $link = $resp->header(Param::LINK);

        $ret = $resp->json();
        if ($link) $ret[Param::LINK] = $link;

        return $ret;
    }
}
