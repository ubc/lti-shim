<?php
namespace UBC\LTI\Specs\Nrps;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Build;

use App\Models\Nrps;
use App\Models\Tool;

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
            [Param::NRPS_SCOPE_URI]
        );
        $ownTool = Tool::getOwnTool();
        $platform = $this->nrps->deployment->platform;
        $key = $ownTool->keys()->first();
        $faker = Faker::create();
        $requestJwt = Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->iss($ownTool->iss)
            ->aud($platform->iss)
            ->iat() // automatically set issued at time
            ->exp(time() + 60)
            ->header(Param::KID, $key->kid)
        // TODO: real nonce protection
            ->claim(Param::NONCE, $faker->md5)
            ->sign($key->key);
        $params = [Param::JWT => $requestJwt];

        $req = Http::withHeaders([
            'Accept' => 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json',
            
            'Authorization' => 'Bearer ' . $accessToken
        ]);
        $resp = $req->get($this->nrps->context_memberships_url);
        return $resp->json();

    }
}