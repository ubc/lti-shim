<?php
namespace UBC\LTI\Specs\Security\Platform;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Easy\Load;

use App\Models\Tool;

use UBC\LTI\LTIException;
use UBC\LTI\Param;
use UBC\LTI\Specs\JwsUtil;
use UBC\LTI\Specs\ParamChecker;

// Part of the LTI security spec. LTI services require authentication using
// OAuth2 tokens. This class deals with issuing these tokens. Note that while
// Laravel Passport also issues client credential tokens, it does not support
// the JWT authentication method that LTI 1.3 requires.
class PlatformOAuthToken
{
    public const VALID_SCOPES = array(
        Param::NAMES_AND_ROLES_SCOPE_URI
    );

    private Request $request; // laravel request object
    private ParamChecker $checker;

    private bool $hasAuthRequest = false; // true if checkAuthRequest() passed

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->checker = new ParamChecker($request->input());
    }

    // second stage of LTI launch on the platform side, we need to check
    // the authentication request sent by the tool.
    public function processTokenRequest()
    {
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
        $jwsUtil = new JwsUtil($jwtString);
        $kid = $jwsUtil->getKid();
        $clientId = $jwsUtil->getClaim(Param::SUB);
        // now we know the tool and can verify the JWT
        $tool = Tool::firstWhere('client_id', $clientId);
        $jwk = $tool->getKey($kid)->public_key;
        $jwt = Load::jws($jwtString);
        $jwt = $jwt->algs([Param::RS256]) // The algorithms allowed to be used
                   ->exp() // We check the "exp" claim
                   ->iat(5000) // We check the "iat" claim. Leeway is 5000ms
                   ->iss($tool->iss)
                   ->key($jwk); // Key used to verify the signature
        try {
            // check signature
            $jwt = $jwt->run();
            // TODO: verify aud
            // TODO: add replay protection based on jti
        } catch(\Exception $e) { // invalid signature throws a bare Exception
            throw new LTIException(
                'Invalid client assertion JWT: ' . $e->getMessage(), 0, $e);
        }
        // scopes are space delimited, we want to throw an error if it's a scope
        // we don't support
        $scopes = explode(' ', $this->request->input(Param::SCOPE));
        foreach ($scopes as $scope) {
            if (!in_array($scope, self::VALID_SCOPES)) {
                throw new LTIException('Unsupported scope: ' . $scope);
            }
        }
        // TODO: create access token
        return [
            Param::ACCESS_TOKEN => 'token',
            Param::TOKEN_TYPE => Param::TOKEN_TYPE_VALUE,
            Param::EXPIRES_IN => 3600,
            Param::SCOPE => $this->request->input(Param::SCOPE)
        ];
    }

}
