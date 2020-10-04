<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Easy\Load;

use App\Models\Tool;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\AccessToken;
use UBC\LTI\Specs\Security\Nonce;

// Part of the LTI security spec. LTI services require authentication using
// OAuth2 tokens. This class deals with issuing these tokens. Note that while
// Laravel Passport also issues client credential tokens, it does not support
// the JWT authentication method that LTI 1.3 requires.
class PlatformAccessToken
{
    private AccessToken $tokenHelper;
    private LtiLog $ltiLog;
    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->ltiLog = new LtiLog('Access Token (Platform)');
        $this->tokenHelper = new AccessToken($this->ltiLog);
        $this->checker = new ParamChecker($request->input(), $this->ltiLog);
    }

    /**
     * Validate the access token request JWT, if it's valid, reply with an
     * access token.
     */
    public function processTokenRequest()
    {
        $this->ltiLog->info('Request received', $this->request);
        $requiredParams = [
            Param::GRANT_TYPE,
            Param::CLIENT_ASSERTION,
            Param::CLIENT_ASSERTION_TYPE,
            Param::SCOPE
        ];
        $this->checker->requireParams($requiredParams);
        $requiredValues = [
            Param::GRANT_TYPE => Param::GRANT_TYPE_VALUE,
            Param::CLIENT_ASSERTION_TYPE => Param::CLIENT_ASSERTION_TYPE_VALUE
        ];
        $this->checker->requireValues($requiredValues);
        // we can't validate the JWT's signature if we don't know what tool
        // created it, so first to try get the tool's client_id
        $jwtString = $this->request->input(Param::CLIENT_ASSERTION);
        $jwsUtil = new JwsUtil($jwtString, $this->ltiLog);
        $kid = $jwsUtil->getKid();
        $this->ltiLog->debug('Using kid: ' . $kid);
        $clientId = $jwsUtil->getClaim(Param::SUB);
        // now that we know the tool, we can verify the JWT
        $tool = Tool::firstWhere('client_id', $clientId);
        $this->ltiLog->debug('Found tool requesting access token', $tool);
        $jwk = $tool->getKey($kid)->public_key;
        $jwt = Load::jws($jwtString);
        // Note that we're not validating the iss because the spec is not clear
        // on what should be in it. The spec could be read two ways, since it's
        // not clear whether the access token request counts as an LTI message.
        // Either the iss needs to be the OAuth issuer (not an LTI message) or
        // it needs to be the OAuth client id (is an LTI message). I've seen it
        // implemented both ways.
        $jwt = $jwt->algs([Param::RS256]) // The algorithms allowed to be used
                   ->key($jwk); // Key used to verify the signature
        try {
            // check signature
            $jwt = $jwt->run();
            JwsUtil::verifyTimestamps($jwt, $this->ltiLog);
            // replay protection based on jti
            $jti = $jwt->claims->jti();
            Nonce::store($jti,
                ($jwt->claims->exp() + JwsUtil::TOKEN_LEEWAY) - time());
            if (Nonce::isValid($jti)) Nonce::used($jti);
            else throw new LtiException($this->ltiLog->msg('Replayed JTI'));
            // TODO: verify aud
        } catch(\Exception $e) { // invalid signature throws a bare Exception
            throw new LtiException($this->ltiLog->msg(
                'Invalid client assertion JWT: ' . $e->getMessage()), 0, $e);
        }
        // scopes are space delimited
        $scopes = explode(' ', $this->request->input(Param::SCOPE));
        // create access token
        $token = $this->tokenHelper->create($tool, $scopes);

        $ret = [
            Param::ACCESS_TOKEN => $token,
            Param::TOKEN_TYPE => Param::TOKEN_TYPE_VALUE,
            Param::EXPIRES_IN => AccessToken::EXPIRY_TIME,
            Param::SCOPE => $this->request->input(Param::SCOPE)
        ];
        $this->ltiLog->notice('Request complete, token issued: ' .
                              json_encode($ret), $this->request, $tool);
        return $ret;
    }

}
