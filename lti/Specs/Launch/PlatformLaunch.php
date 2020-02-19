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
use UBC\LTI\Filters\WhitelistFilter;
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
            new WhitelistFilter()
        ];
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        $ltiSession = $this->getLtiSession();

        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = $deployment->tool;

        $params = [
            Param::ISS => config('lti.iss'),
            // TODO: filter param
            Param::LOGIN_HINT => $ltiSession->session[Param::LOGIN_HINT],
            Param::TARGET_LINK_URI => $tool->target_link_uri,
            Param::CLIENT_ID => $tool->client_id,
            Param::LTI_DEPLOYMENT_ID => $deployment->lti_deployment_id,
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT)
        ];
        $params = $this->applyFilters($params);
        return $params;
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function checkAuthRequest()
    {
        $ltiSession = $this->getLtiSession();

        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = $deployment->tool;

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

        $ltiSession = $this->getLtiSession();
        $deployment = Deployment::find($ltiSession->session['deployment_id']);
        $tool = $deployment->tool;

        $resp = [
            'state' => $this->request->input('state')
        ];

        $time = time();
        $key = Platform::getOwnPlatform()->keys()->first();
        $params = [
            Param::TYP => 'JWT',
            Param::ALG => Param::RS256,
            Param::ISS => config('lti.iss'),
            Param::SUB => $ltiSession->session[Param::LOGIN_HINT], // user id
            Param::AUD => $tool->client_id,
            Param::EXP => $time + 3600, // expires 1 hour, might want to tighten
            Param::IAT => $time, // issued at
            Param::KID => $key->kid,
            Param::NONCE => $this->request->input('nonce'),
            Param::MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
            Param::ROLES_URI => [],
            Param::VERSION_URI => '1.3.0',
            Param::DEPLOYMENT_ID_URI => $deployment->lti_deployment_id,
            Param::TARGET_LINK_URI_URI => $tool->target_link_uri,
            // TODO real resource link
            Param::RESOURCE_LINK_URI => ['id' => 'fake_resource_link_id']
        ];
        $params = $this->applyFilters($params);
        $token = Build::jws()
            ->typ($params[Param::TYP])
            ->alg($params[Param::ALG])
            ->iss($params[Param::ISS])
            ->sub($params[Param::SUB])
            ->aud($params[Param::AUD])
            ->exp($params[Param::EXP])
            ->iat($params[Param::IAT])
            ->header(Param::KID, $params[Param::KID])
            ->claim(Param::NONCE, $params[Param::NONCE])
            ->claim(Param::MESSAGE_TYPE_URI, $params[Param::MESSAGE_TYPE_URI])
            ->claim(Param::ROLES_URI, $params[Param::ROLES_URI])
            ->claim(Param::VERSION_URI, $params[Param::VERSION_URI])
            ->claim(Param::DEPLOYMENT_ID_URI, $params[Param::DEPLOYMENT_ID_URI])
            ->claim(Param::TARGET_LINK_URI_URI,
                    $params[Param::TARGET_LINK_URI_URI])
            ->claim(Param::RESOURCE_LINK_URI, $params[Param::RESOURCE_LINK_URI])
            ->sign($key->key);
        $resp['id_token'] = $token;
        $resp = $this->applyFilters($resp);

        return $resp;
    }

    private function applyFilters(array $params): array
    {
        foreach($this->filters as $filter) {
            $params = $filter->filter($params);
        }
        return $params;
    }

    private function getLtiSession(): LtiSession
    {
        if (!$this->request->has(Param::LTI_MESSAGE_HINT)) {
            throw new LTIException('No LTI session found.');
        }
        $state = EncryptedState::decrypt(
            $this->request->input(Param::LTI_MESSAGE_HINT));
        $ltiSession = LtiSession::find($state->claims->get('lti_session'));
        if (!$ltiSession) {
            throw new LTIException('Invalid LTI session, is it expired?');
        }
        return $ltiSession;
    }
}
