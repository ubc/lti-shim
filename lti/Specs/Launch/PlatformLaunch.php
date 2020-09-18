<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Build;

use App\Models\Deployment;
use App\Models\LtiFakeUser;
use App\Models\LtiRealUser;
use App\Models\LtiSession;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\EncryptedState;
use UBC\LTI\LtiException;
use UBC\LTI\LtiLog;
use UBC\LTI\Param;
use UBC\LTI\Specs\ParamChecker;

use UBC\LTI\Specs\Launch\Filters\CourseContextFilter;
use UBC\LTI\Specs\Launch\Filters\DeploymentFilter;
use UBC\LTI\Specs\Launch\Filters\LaunchPresentationFilter;
use UBC\LTI\Specs\Launch\Filters\NrpsFilter;
use UBC\LTI\Specs\Launch\Filters\ResourceLinkFilter;
use UBC\LTI\Specs\Launch\Filters\UserFilter;
use UBC\LTI\Specs\Launch\Filters\WhitelistFilter;

// we're acting as the Platform
// the main idea is that we supply this object with the params that we receive
// and get the appropriate response params back
class PlatformLaunch
{
    private Request $request; // laravel request object
    private ParamChecker $checker;
    private LtiLog $ltiLog;
    private LtiSession $ltiSession;
    private array $filters;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
        $this->filters = [
            new DeploymentFilter(),
            new WhitelistFilter(),
            new UserFilter(),
            new ResourceLinkFilter(),
            new LaunchPresentationFilter(),
            new CourseContextFilter(),
            new NrpsFilter()
        ];
        $this->ltiSession = LtiSession::getSession($this->request);
        $this->ltiLog = new LtiLog('Launch (Platform Side)');
    }

    // first stage of the LTI launch on the platform side, we need to send the
    // login params to the tool.
    public function getLoginParams(): array
    {
        $this->ltiLog->debug("Send Login", $this->request, $this->ltiSession);

        $deployment = $this->ltiSession->deployment;
        $tool = $this->ltiSession->tool;
        $user = $this->ltiSession->lti_real_user;


        $params = [
            Param::ISS => config('lti.iss'),
            Param::LOGIN_HINT => $user->login_hint,
            Param::TARGET_LINK_URI => $tool->target_link_uri,
            Param::CLIENT_ID => $tool->client_id,
            Param::LTI_DEPLOYMENT_ID => $deployment->lti_deployment_id,
            Param::LTI_MESSAGE_HINT =>
                $this->request->input(Param::LTI_MESSAGE_HINT)
        ];
        $this->ltiLog->debug('Pre-filter: ' . json_encode($params),
            $this->request, $this->ltiSession);
        $params = $this->applyFilters($params);
        $this->ltiLog->debug('Post-filter: ' . json_encode($params),
            $this->request, $this->ltiSession);
        return [
            'response' => $params,
            'oidc_login_url' => $tool->oidc_login_url
        ];
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function checkAuthRequest()
    {
        $this->ltiLog->debug("Receive Auth Request",
            $this->request, $this->ltiSession);
        $tool = $this->ltiSession->tool;
        $user = $this->ltiSession->lti_real_user;

        $requiredValues = [
            // static values
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // dynamic values
            Param::LOGIN_HINT => $user->login_hint,
            Param::CLIENT_ID => $tool->client_id
        ];
        $this->ltiLog->debug('Pre-filter: ' . json_encode($requiredValues),
            $this->request, $this->ltiSession);
        $requiredValues = $this->applyFilters($requiredValues);
        $this->ltiLog->debug('Post-filter: ' . json_encode($requiredValues),
            $this->request, $this->ltiSession);
        // TODO: validate redirect_uri, valid redirect_uri is supposed to be
        // pre-registered and we need to make sure it matches what we have

        $this->checker->requireValues($requiredValues);

        $this->hasAuthRequest = true;
    }

    // third and final stage of the LTI launch on the platform side, we need
    // to generate the id_token JWT
    public function getAuthResponse(): array
    {
        $this->ltiLog->debug("Send Auth Response",
            $this->request, $this->ltiSession);
        // cannot generate the auth response without an auth request
        if (!$this->hasAuthRequest) $this->checkAuthRequest();

        $deployment = $this->ltiSession->deployment;
        $tool = $this->ltiSession->tool;

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
            Param::SUB => $this->ltiSession->token[Param::SUB], // user id
            Param::AUD => $tool->client_id,
            Param::EXP => $time + 3600, // expires 1 hour, might want to tighten
            Param::IAT => $time, // issued at
            Param::NBF => $time, // not before
            Param::NONCE => $this->request->input('nonce'),
            Param::MESSAGE_TYPE_URI => 'LtiResourceLinkRequest',
            Param::ROLES_URI => $this->ltiSession->token[Param::ROLES_URI],
            Param::VERSION_URI => '1.3.0',
            Param::DEPLOYMENT_ID_URI => $deployment->lti_deployment_id,
            Param::TARGET_LINK_URI_URI => $tool->target_link_uri,
            // TODO real resource link
            Param::RESOURCE_LINK_URI =>
                $this->ltiSession->token[Param::RESOURCE_LINK_URI]
        ];
        // pass through optional params if they exist
        $optionalParams = [
            Param::NAME,
            Param::EMAIL,
            Param::LAUNCH_PRESENTATION_URI,
            Param::CONTEXT_URI,
            Param::NRPS_CLAIM_URI
        ];
        foreach ($optionalParams as $optionalParam) {
            if (isset($this->ltiSession->token[$optionalParam])) {
                $payload[$optionalParam] =
                    $this->ltiSession->token[$optionalParam];
            }
        }
        $this->ltiLog->debug('Pre-filter id_token: ' . json_encode($payload),
            $this->request, $this->ltiSession);
        // filter all params
        $payload = $this->applyFilters($payload);
        $this->ltiLog->debug('Post-filter id_token: ' . json_encode($payload),
            $this->request, $this->ltiSession);
        // header params (typ, alg, kid) cannot be loaded using the payload()
        // function so has to be specified separately (and won't be filtered)
        $this->ltiLog->debug('id_token: key: '. $key->id .' kid: ' . $key->kid,
            $this->request, $this->ltiSession);
        $token = Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->header(Param::KID, $key->kid)
            ->payload($payload)
            ->sign($key->key);
        $resp['id_token'] = $token;

        $this->ltiLog->debug('Pre-filter params: ' . json_encode($resp),
            $this->request, $this->ltiSession);
        $resp = $this->applyFilters($resp);
        $this->ltiLog->debug('Post-filter params: ' . json_encode($resp),
            $this->request, $this->ltiSession);

        $fakeUser = LtiFakeUser::getByRealUser(
            $this->ltiSession->course_context_id,
            $this->ltiSession->tool_id,
            $this->ltiSession->lti_real_user
        );

        $this->ltiLog->notice('Launch completed', $this->request,
            $this->ltiSession, $this->ltiSession->course_context,
            $this->ltiSession->lti_real_user, $fakeUser
        );

        return [
            'response' => $resp,
            'auth_resp_url' => $tool->auth_resp_url
        ];
    }

    private function applyFilters(array $params): array
    {
        $this->ltiLog->debug("Applying Filters", $this->request,
            $this->ltiSession);
        foreach ($this->filters as $filter) {
            $params = $filter->filter($params, $this->ltiSession);
        }
        return $params;
    }
}
