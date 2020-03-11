<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\LtiSession;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\EncryptedState;
use UBC\LTI\Filters\DeploymentFilter;
use UBC\LTI\Filters\WhitelistFilter;
use UBC\LTI\Filters\UserFilter;
use UBC\LTI\LTIException;
use UBC\LTI\Param;
use UBC\LTI\Specs\ParamChecker;


// we're acting as the Platform
// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class PlatformLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;
    private array $filters;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
        $this->filters = [
            new DeploymentFilter(),
            new WhitelistFilter(),
            new UserFilter()
        ];
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        $ltiSession = LtiSession::getSession($this->request);

        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = Tool::find($ltiSession->session['tool_id']);

        $params = [
            Param::ISS => config('lti.iss'),
            Param::LOGIN_HINT => $ltiSession->session[Param::LOGIN_HINT],
            Param::TARGET_LINK_URI => $tool->target_link_uri,
            Param::CLIENT_ID => $tool->client_id,
            Param::LTI_DEPLOYMENT_ID => $deployment->lti_deployment_id,
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT)
        ];
        $params = $this->applyFilters($params, $ltiSession);
        return [
            'response' => $params,
            'oidc_login_url' => $tool->oidc_login_url
        ];
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function checkAuthRequest()
    {
        $ltiSession = LtiSession::getSession($this->request);

        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = Tool::find($ltiSession->session['tool_id']);

        $requiredValues = [
            // static values
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // dynamic values
            Param::LOGIN_HINT => $ltiSession->session[Param::LOGIN_HINT],
            Param::CLIENT_ID => $tool->client_id
        ];
        $requiredValues = $this->applyFilters($requiredValues, $ltiSession);
        // TODO: validate redirect_uri, valid redirect_uri is supposed to be
        // pre-registered and we need to make sure it matches what we have
        // TODO: nonce validation will probably needs to be tied to client_id
        // and other such dynamic values somehow, so we can be sure that the
        // original login request came from us

        $this->checker->requireValues($requiredValues);

        $this->hasAuthRequest = true;
    }

    // third and final stage of the LTI launch on the platform side, we need
    // to generate the id_token JWT
    public function getAuthResponse(): array
    {
        // cannot generate the auth response without an auth request
        if (!$this->hasAuthRequest) $this->checkAuthRequest();

        $ltiSession = LtiSession::getSession($this->request);
        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = Tool::find($ltiSession->session['tool_id']);

        $resp = [];
        if ($this->request->has('state')) {
            $resp['state'] = $this->request->input('state');
        }

        $time = time();
        $key = Platform::getOwnPlatform()->keys()->first();
        $payload = [
            Param::TYP => Param::JWT,
            Param::KID => $key->kid,
            Param::ISS => config('lti.iss'),
            Param::SUB => $ltiSession->session[Param::SUB], // user id
            Param::AUD => $tool->client_id,
            Param::EXP => $time + 3600, // expires 1 hour, might want to tighten
            Param::IAT => $time, // issued at
            Param::NBF => $time, // not before
            Param::NONCE => $this->request->input('nonce'),
            Param::MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
            Param::ROLES_URI => $ltiSession->session[Param::ROLES_URI],
            Param::VERSION_URI => '1.3.0',
            Param::DEPLOYMENT_ID_URI => $deployment->lti_deployment_id,
            Param::TARGET_LINK_URI_URI => $tool->target_link_uri,
            // TODO real resource link
            Param::RESOURCE_LINK_URI => ['id' => 'fake_resource_link_id']
        ];
        // optional params that might not be set
        if (isset($ltiSession->session[Param::NAME])) {
            $payload[Param::NAME] = $ltiSession->session[Param::NAME];
        }
        if (isset($ltiSession->session[Param::EMAIL])) {
            $payload[Param::EMAIL] = $ltiSession->session[Param::EMAIL];
        }
        $payload = $this->applyFilters($payload, $ltiSession);
        // header params (typ, alg, kid) cannot be loaded using the payload()
        // function so has to be specified separately (and won't be filtered)
        $token = Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->header(Param::KID, $key->kid)
            ->payload($payload)
            ->sign($key->key);
        $resp['id_token'] = $token;
        $resp = $this->applyFilters($resp, $ltiSession);

        return [
            'response' => $resp,
            'auth_resp_url' => $tool->auth_resp_url
        ];
    }

    private function applyFilters(array $params, LtiSession $session): array
    {
        foreach($this->filters as $filter) {
            $params = $filter->filter($params, $session);
        }
        return $params;
    }
}
