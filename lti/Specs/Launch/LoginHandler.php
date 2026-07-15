<?php
namespace UBC\LTI\Specs\Launch;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use League\Uri\Uri;
use League\Uri\UriInfo;
use League\Uri\UriModifier;

use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Utils\UriUtil;

use App\Models\DeepLinkContentItem;
use App\Models\LtiSession;
use App\Models\Platform;
use App\Models\Tool;

/**
 * FIRST STAGE of LTI launch, the OpenID Connect (OIDC) Login.
 *
 * We first receive an OIDC login request from the platform.
 * We will then generate an LtiSession that identifies this launch.
 * We then will send an OIDC login request to the target tool.
 */
class LoginHandler
{

    private LtiLog $ltiLog;
    private LtiSession $session;
    private Request $request;
    private ParamChecker $checker;
    private ?DeepLinkContentItem $contentItem = null;

    public function __construct(Request $request, string $streamId)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (OIDC Login)', $streamId);
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
    }

    /**
     * Returns the OIDC login parameters we should send to the target tool. This
     * is the shim acting as a platform.
     */
    public function sendLogin(): Response
    {
        $this->ltiLog->info('Platform Side, send OIDC login.', $this->request);
        $loginParams = [
            // REQUIRED
            Param::ISS => config('lti.iss'),
            Param::LOGIN_HINT => $this->session->createEncryptedId(),
            Param::TARGET_LINK_URI => $this->session->tool->target_link_uri,
            // OPTIONAL
            // while client_id is technically optional, it seems that because
            // platforms usually send it, some tools started expecting it to be
            // present, so it's better for us to send it
            Param::CLIENT_ID => $this->session->tool->client_id
        ];
        $this->ltiLog->info('After generating login params', $this->request);
        $ret = response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'OIDC Login',
                'formUrl' => $this->getLaunchUrl(),
                'params' => $loginParams
            ]
        );
        $this->ltiLog->info('After generating response', $this->request);
        return $ret;
    }

    /**
     * Just validates to see if the OIDC login request we received from the
     * platform was valid. This is the shim acting as a tool.
     */
    public function recvLogin()
    {
        $this->ltiLog->info('Tool Side, recv OIDC login: ' .
            json_encode($this->request->input()), $this->request);

        $requiredParams = [
            Param::ISS,
            Param::LOGIN_HINT,
            Param::TARGET_LINK_URI
        ];
        $this->checker->requireParams($requiredParams);

        // needs to be a platform that the shim has been configured for
        if (!Platform::hasIss($this->request->input(Param::ISS)))
            throw new LtiException($this->ltiLog->msg("Unknown platform iss.",
                $this->request));
        // check to see if this is a deep link content item launch
        if ($this->request->route('deepLinkContentItemId')) {
            $contentItem = DeepLinkContentItem::find(
                $this->request->route('deepLinkContentItemId'));
            if (!$contentItem)
                throw new LtiException($this->ltiLog->msg('Invalid content item.'));
            $this->contentItem = $contentItem;
        }
        // recommended security check from OIDC. Not currently necessary for the
        // shim, since we don't actually use the value, but here just in case.
        $target = $this->request->input(Param::TARGET_LINK_URI);
        if (!UriUtil::isSameSite(config('lti.iss'), $target))
            throw new LtiException($this->ltiLog->msg(
                "target_link_uri is some other site: $target", $this->request));
        $this->session = $this->createSession();
    }

    public function getSession(): LtiSession
    {
        if ($this->session)
            return $this->session;
        throw new LtiException('LtiSession not created, please recvLogin()');
    }

    /**
     * Create an LtiSession entry for this launch.
     */
    private function createSession(): LtiSession
    {
        // find out what tool we need to relay this launch to
        $tool = Tool::find($this->request->route(Tool::TARGET_TOOL_PARAM));
        if (!$tool)
            throw new LtiException($this->ltiLog->msg('Invalid target tool.'));
        // find out what platform we're coming from
        $platform = Platform::getByIss($this->request->input(Param::ISS));
        // check that the shim has been configured to link the given platform
        // and tool
        $platformClient = $tool->getPlatformClient($platform->id);
        if (!$platformClient)
            throw new LtiException($this->ltiLog->msg(
                'PlatformClient not configured for given platform and tool',
                $this->request, $platform, $tool));
        // check that the client_id we just retrieved matches the optional
        // client_id sent in the launch (if present)
        if ($this->request->has(Param::CLIENT_ID) &&
            $this->request->get(Param::CLIENT_ID) != $platformClient->client_id)
        {
            throw new LtiException($this->ltiLog->msg(
                'client_id does not match the one on record',
                $this->request, $platformClient));
        }

        $sessionType = Param::SESSION_TYPE_REG;
        if ($this->isMidwayLookupOnly())
            $sessionType = Param::SESSION_TYPE_LOOKUP;

        // keep track of params that needs to be used at some later step in the
        // launch
        $state = [
            Param::SESSION_TYPE => $sessionType,
            // needs to be passed back in the auth req stage
            Param::LOGIN_HINT => $this->request->input(Param::LOGIN_HINT)
        ];
        // optional params that might not be present
        // - lti_message_hint needs to be passed back in the auth req step
        if ($this->request->has(Param::LTI_MESSAGE_HINT)) {
            $state[Param::LTI_MESSAGE_HINT] =
                $this->request->input(Param::LTI_MESSAGE_HINT);
        }
        // - lti_deployment_id must match the deployment ID given later in
        // the original platform's auth resp step's id_token
        if ($this->request->has(Param::LTI_DEPLOYMENT_ID)) {
            $state[Param::LTI_DEPLOYMENT_ID] =
                $this->request->input(Param::LTI_DEPLOYMENT_ID);
        }

        // the lti session is how we keep track of server side state (which lets
        // us avoid cookies and all the trouble that now entails)
        $session = new LtiSession();
        $session->platform_client_id = $platformClient->id;
        $session->tool_id = $tool->id;
        $session->state = $state;
        $session->log_stream = $this->ltiLog->getStreamId();
        $session->save();

        return $session;
    }

    private function isMidwayLookupOnly(): bool
    {
        $targetLinkUri = $this->request->input(Param::TARGET_LINK_URI);
        $targetLinkUri = Uri::createFromString($targetLinkUri);
        $midwayLookupUri = route('lti.launch.midway');
        $midwayLookupUri = Uri::createFromString($midwayLookupUri);
        $midwayLookupUri = UriModifier::appendSegment($midwayLookupUri,
                                                     Param::MIDWAY_LOOKUP_PATH);
        if ($targetLinkUri->getPath() == $midwayLookupUri->getPath())
            return true;
        return false;
    }

    private function getLaunchUrl(): string
    {
        $url = $this->session->tool->oidc_login_url;
        if ($this->contentItem) {
            $url = $this->contentItem->url;
        }
        return $url;
    }

}
