<?php
namespace UBC\LTI\Specs\DeepLink;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use League\Uri\Uri;

use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\Nonce;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Utils\UriUtil;

use App\Models\LtiSession;

/**
 * SECOND STAGE of LTI launch, the Authorization Request
 *
 * We first receive an auth req from the target tool.
 * We restore state from LtiSession.
 * We then send an auth req to the originating platform.
 */
class AuthReqHandler
{

    private LtiLog $ltiLog;
    private LtiSession $session;
    private ParamChecker $checker;
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch (Auth Req)');
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
        $this->loadSession();
    }

    /**
     * Returns the auth req parameters we should send to the originating
     * platform. This is the shim acting as a tool.
     */
    public function sendAuth(): Response
    {
        $this->receiveAuth();
        $this->ltiLog->info('Tool Side, send auth req.', $this->request);
        $authReqParams = [
            // REQUIRED static
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // REQUIRED dynamic
            Param::CLIENT_ID => $this->session->platform_client->client_id,
            Param::NONCE => Nonce::create(),
            Param::REDIRECT_URI => route('lti.launch.redirect'),
            Param::LOGIN_HINT => $this->session->token[Param::LOGIN_HINT],
            // Not required by spec, but needed for us to track session
            Param::STATE => $this->session->createEncryptedId()
        ];
        if (isset($this->session->token[Param::LTI_MESSAGE_HINT])) {
            $authReqParams[Param::LTI_MESSAGE_HINT] =
                $this->session->token[Param::LTI_MESSAGE_HINT];
        }
        return response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'Auth Request',
                'formUrl' =>
                        $this->session->platform_client->platform->auth_req_url,
                'params' => $authReqParams
            ]
        );
    }

    /**
     * Just validates to see if the auth req we received from the tool was
     * valid. This is the shim acting as a platform.
     */
    private function receiveAuth()
    {
        $this->ltiLog->info('Platform Side, recv auth req: ' .
            json_encode($this->request->input()), $this->request);

        // required params that needs to be present
        $requiredParams = [
            Param::REDIRECT_URI,
            //Param::LOGIN_HINT, // should be already checked by loadSession()
            Param::NONCE,
        ];
        $this->checker->requireParams($requiredParams);
        // required params that needs to have matching values
        $requiredValues = [
            // static values
            Param::SCOPE => Param::OPENID,
            Param::RESPONSE_TYPE => Param::ID_TOKEN,
            Param::RESPONSE_MODE => Param::FORM_POST,
            Param::PROMPT => Param::NONE,
            // dynamic values
            Param::CLIENT_ID => $this->session->tool->client_id
        ];
        $this->checker->requireValues($requiredValues);

        // technically, the redirect uri, aka auth resp url, needs to match
        // what was configured for the target tool. However, I suspect a strict
        // match is probably too restrictive. Tools might want to add queries
        // and such to the redirect_uri. So we'll just make sure they're the
        // same site.
        $redirectUri = $this->request->input(Param::REDIRECT_URI);
        if (!UriUtil::isSameSite($this->session->tool->auth_resp_url,
                                 $redirectUri))
        {
            throw new LtiException($this->ltiLog->msg(
                'redirect_uri not same site as configured (' .
                $this->session->tool->auth_resp_url . ')', $this->request));
        }

        // nonce and state needs to be stored for the next auth resp step
        $sessionState = $this->session->token;
        $sessionState[Param::NONCE] = $this->request->input(Param::NONCE);
        $sessionState[Param::REDIRECT_URI] = $redirectUri;
        if ($this->request->has(Param::STATE))
            $sessionState[Param::STATE] = $this->request->input(Param::STATE);

        $this->session->token = $sessionState;
        $this->session->save();
    }

    /**
     * Load the LtiSession from the login_hint param.
     */
    private function loadSession()
    {
        if (!$this->request->has(Param::LOGIN_HINT))
            throw new LtiException($this->ltiLog->msg(
                'Missing login_hint in auth request', $this->request));

        $this->session = LtiSession::decodeEncryptedId(
            $this->request->input(Param::LOGIN_HINT));
        $this->ltiLog->setStreamid($this->session->log_stream);
    }
}
