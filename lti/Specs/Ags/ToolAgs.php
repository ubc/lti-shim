<?php
namespace UBC\LTI\Specs\Ags;

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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

    public function getLineitems(): array
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

        $req = Http::withHeaders([
            'Accept' => [
                Param::AGS_MEDIA_TYPE_LINEITEM,
                Param::AGS_MEDIA_TYPE_LINEITEMS
            ],
            'Authorization' => 'Bearer ' . $accessToken
        ]);

        $resp = $req->get($this->ags->lineitems);

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

        return $resp->json();
    }
}