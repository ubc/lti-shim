<?php
namespace UBC\LTI\Specs\DeepLink;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Jose\Easy\Build;

use App\Models\DeepLink;
use App\Models\Tool;

use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\JwsToken;
use UBC\LTI\Specs\Security\Nonce;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;

/**
 * FOURTH (LAST) STAGE of Deep Linking launch, the Deep Link Return.
 *
 * We restore Deep Link state from the 'data' claim.
 * We then relay filtered data back to the originating platform's return url.
 */
class ReturnHandler
{
    private DeepLink $dl;
    private LtiLog $ltiLog;
    private LtiSession $session;
    private ParamChecker $checker;
    private Request $request;

    private array $filters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Launch Deep Link (Return)');
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
    }

    /**
     * Returns the OIDC login parameters we should send to the target tool. This
     * is the shim acting as a platform.
     */
    public function sendReturn(): Response
    {
        $jwt = $this->receiveReturn();

        $time = time();
        // required params
        $payload = [
            Param::ISS => $this->dl->platform_client->client_id,
            param::AUD => $this->dl->deployment->platform->iss,
            Param::EXP => $time + Param::EXP_TIME, // expires in 1 hour
            Param::IAT => $time, // issued at
            Param::NBF => $time, // not before
            Param::MESSAGE_TYPE_URI => Param::MESSAGE_TYPE_DEEP_LINK_RESPONSE,
            Param::VERSION_URI => Param::VERSION_130,
            Param::DEPLOYMENT_ID_URI =>
                                 $this->dl->deployment->lti_deployment_id,
            Param::NONCE => Nonce::create(),
            Param::DL_DATA_URI => $this->dl->state,
            // don't know what could be in there yet, so just going to pass it
            // through as is
            Param::DL_CONTENT_ITEMS_URI => $jwt[Param::DL_CONTENT_ITEMS_URI],
        ];
        // optional params
        if ($this->dl->state) $payload[Param::DL_DATA_URI] = $this->dl->state;
        if (isset($jwt[Param::DL_MSG]))
            $payload[Param::DL_MSG] = $jwt[Param::DL_MSG];
        if (isset($jwt[Param::DL_LOG]))
            $payload[Param::DL_LOG] = $jwt[Param::DL_LOG];
        if (isset($jwt[Param::DL_ERRORMSG]))
            $payload[Param::DL_ERRORMSG] = $jwt[Param::DL_ERRORMSG];
        if (isset($jwt[Param::DL_ERRORLOG]))
            $payload[Param::DL_ERRORLOG] = $jwt[Param::DL_ERRORLOG];

        $key = Tool::getOwnTool()->getKey();

        $params = [
            Param::JWT => Build::jws()
                               ->typ(Param::JWT)
                               ->alg(Param::RS256)
                               ->header(Param::KID, $key->kid)
                               ->payload($payload)
                               ->sign($key->key)
        ];

        $this->ltiLog->info('Tool Side, send deep link return: ' .
            json_encode($payload), $this->request);
        return response()->view(
            'lti/launch/auto_submit_form',
            [
                'title' => 'Deep Link Return',
                'formUrl' => $this->dl->return_url,
                'params' => $params
            ]
        );
    }

    /**
     * Return the decoded JWT from the deep link return.
     * We should've gotten a POST request with only 1 param named 'JWT'.
     */
    public function receiveReturn(): array
    {
        $this->ltiLog->info('Platform Side, recv deep link return: ' .
            json_encode($this->request->input()), $this->request);

        $this->checker->requireParams([Param::JWT]);
        // we can't verify the JWT since we don't know what tool it came from,
        // so we need to load the Deep State state first then verify the
        // JWT signature
        $tokenString = $this->request->input(Param::JWT);
        $this->ltiLog->debug('Deep Link return JWT: ' . $tokenString);
        $jwsToken = new JwsToken($tokenString, $this->ltiLog);
        // get the Deep Link entry
        $this->dl = DeepLink::decodeEncryptedId(
                                        $jwsToken->getClaim(Param::DL_DATA_URI));
        // verify the signature unpack into JWT object
        $jwt = $jwsToken->verifyAndDecode($this->dl->tool);
        $jwsToken->checkIssAndAud($this->dl->tool->client_id,
                                  config('lti.iss'));
        $jwsToken->checkNonce(true);

        $this->checkClaims($jwt);
        $this->handleLoggingClaims($jwt);

        return $jwt;
    }

    /**
     * Make sure that the required claims are present and have the required
     * values (if any).
     */
    private function checkClaims(array $jwt)
    {
        $checker = new ParamChecker($jwt, $this->ltiLog);

        $requiredValues = [
            Param::MESSAGE_TYPE_URI => Param::MESSAGE_TYPE_DEEP_LINK_RESPONSE,
            Param::VERSION_URI => Param::VERSION_130,
            Param::DEPLOYMENT_ID_URI =>
                                  $this->dl->deployment->fake_lti_deployment_id,
        ];
        $checker->requireValues($requiredValues);

        if (!isset($jwt[Param::DL_CONTENT_ITEMS_URI])) {
            throw new LtiException($this->ltiLog->msg(
                'Deep Link response missing content items'));
        }
    }

    /**
     * There are claims specifically for logging, add them to the LTI log.
     */
    private function handleLoggingClaims(array $jwt)
    {
        if (isset($jwt[Param::DL_MSG])) {
            $this->ltiLog->info('msg: ' . $jwt[Param::DL_MSG]);
        }
        if (isset($jwt[Param::DL_LOG])) {
            $this->ltiLog->info('log: ' . $jwt[Param::DL_LOG]);
        }
        if (isset($jwt[Param::DL_ERRORMSG])) {
            $this->ltiLog->error('error msg: ' .  $jwt[Param::DL_ERRORMSG]);
        }
        if (isset($jwt[Param::DL_ERRORLOG])) {
            $this->ltiLog->error('error log: ' .  $jwt[Param::DL_ERRORLOG]);
        }
    }

}
