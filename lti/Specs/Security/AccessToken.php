<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Utils\Param;
use UBC\LTI\Specs\ParamChecker;
use UBC\LTI\Specs\Security\Nonce;

class AccessToken
{
    public const ACCESS_TOKEN_STORE = 'accessToken'; // access token cache
    // IMS recommends tokens be valid for 1 hour (3600 seconds),
    // this is the expiry time for tokens we issue
    public const EXPIRY_TIME = 3600;
    // When requesting an access token, we need to send a request JWT, this is
    // the request JWT's expiry time.
    public const REQUEST_EXPIRY_TIME = 3600;
    // only bother caching the access token if it's valid for at least this long
    public const MINIMUM_TOKEN_VALID_TIME = 60;

    // when we request an access token, the response must have these parameters
    private const ACCESS_TOKEN = 'access_token'; // the actual access token
    private const EXPIRES_IN = 'expires_in'; // how long the token is valid for
                                            // in seconds

    // each scope must be mapped to an unique int as we're using it as a unique
    // id for that scope in access token cache
    public static array $VALID_SCOPES = [];

    private LtiLog $ltiLog;

    public function __construct(LtiLog $ltiLog)
    {
        $this->ltiLog = $ltiLog;
        // initialize the valid scopes list if necessary, wish PHP was smart
        // enough to realize that we're merging two const arrays and let us do
        // this at compile time instead of having to do this at run time
        if (!self::$VALID_SCOPES) {
            self::$VALID_SCOPES = array_merge(Param::NRPS_SCOPES,
                                              Param::AGS_SCOPES);
        }
    }

    public function create(Tool $tool, array $scopes): string
    {
        $this->checkScopes($scopes);
        $time = time();
        // IETF has a draft spec for JWT access tokens that we're using as guide
        $jwe = Build::jwe() // We build a JWE
            ->typ(Param::AT_JWT)
            ->iss($tool->client_id)
            ->exp($time + self::EXPIRY_TIME)
            ->iat($time)
            ->aud(config('lti.iss'))
            ->sub($tool->client_id)
            ->alg(Param::RSA_OAEP_256) // key encryption alg
            ->enc(Param::A256GCM) // content encryption alg
            ->zip(Param::ZIP_ALG) // compress the data, DEFLATE alg
            ->crit(['alg', 'enc']); // mark some header parameters as critical
        // keep the size of the token down by shrinking the scope payload
        $shrinkedScopes = [];
        foreach ($scopes as $scope) {
            $shrinkedScopes[] = self::$VALID_SCOPES[$scope];
        }
        $jwe->claim(Param::SCOPE, $shrinkedScopes);
        // encrypt with the public key
        $jwe = $jwe->encrypt(EncryptionKey::getNewestKey()->public_key);
        return $jwe;
    }

    /**
     * Besides the cryptographic integrity of the access token,
     * we need to make sure that the scopes we require are present in the token.
     *
     * Due to how AGS scopes are split into ones that only allow read only and
     * ones that allow all operations, as long as one of the scopes is present
     * in the access token, we treat it as having passed the check. This lets us
     * pass in both the readonly and all access variants in the required scopes
     * list for read only operations.
     *
     * We also need to make sure that a tool isn't accessing an endpoint created
     * by another tool and hence leaking data, so need to make sure the iss
     * match the tool that created the endpoint.
     */
    public function verify(
        string $token,
        Tool $requiredTool,
        array $requiredScopes
    ): JWT {
        $jwt;
        try {
            $jwt = Load::jwe($token) // deserialize the token
                ->algs([Param::RSA_OAEP_256]) // key encryption algo
                ->encs([Param::A256GCM]) // content encryption algo
                ->exp()
                ->iat()
                ->key(EncryptionKey::getNewestKey()->key) // private key decrypt
                ->run();
        }
        catch(\Exception $e) {
            Log::error("Unable to verify access token.");
            throw new LtiException($this->ltiLog->msg(
                    'Invalid access token: ' . $e->getMessage()), 0, $e);
        }
        // make sure it's the right tool
        if ($requiredTool->client_id != $jwt->claims->iss()) {
            throw new LtiException($this->ltiLog->msg(
                "This access token is not allowed on this endpoint"));
        }
        // make sure at least one of the required scopes are present
        $scopes = $jwt->claims->get(Param::SCOPE);
        $hasScope = false;
        foreach ($requiredScopes as $requiredScope) {
            if (in_array(self::$VALID_SCOPES[$requiredScope], $scopes)) {
                $hasScope = true;
                break;
            }
        }
        if (!$hasScope) {
            throw new LtiException($this->ltiLog->msg(
                "This access token does not have scopes required: " .
                json_encode($requiredScopes)));
        }
        return $jwt;
    }

    /**
     * Shim requesting an access token from a platform. We want to cache the
     * access token so that we don't have to send an access token request for
     * every service call.
     *
     * While technically we should be able to request multiple scopes for an
     * access token, it's recommended to request only have 1 scope per access
     * token as Canvas is not happy with multiple scopes. This doesn't apply
     * to access tokens issued by the shim itself (hopefully).
     */
    public function request(
        Platform $platform,
        Tool $tool,
        array $scopes
    ): string {
        $this->checkScopes($scopes);

        // see if we can get from cache
        $store = Cache::store(self::ACCESS_TOKEN_STORE);
        $cacheKey = self::getCacheKey($platform->id, $scopes);
        $token = $store->get($cacheKey);
        if ($token) {
            $this->ltiLog->debug('Access token loaded from cache.');
            return $token;
        }
        $this->ltiLog->debug('Access token not in cache, try to get one.');

        if (count($scopes) > 1)
            $this->ltiLog->warning('Some LMS have issues with multiple scopes');
        // not in cache, request an access token
        $requestJwt = self::getRequestJwt($platform, $tool, $scopes);
        $params = [
            Param::GRANT_TYPE => Param::GRANT_TYPE_VALUE,
            Param::CLIENT_ASSERTION_TYPE => Param::CLIENT_ASSERTION_TYPE_VALUE,
            Param::CLIENT_ASSERTION => $requestJwt,
            Param::SCOPE => implode(' ', $scopes)
        ];
        $timeBefore = time();
        $resp = Http::asForm()->post($platform->access_token_url, $params);
        $timeTaken = time() - $timeBefore;

        if ($resp->failed())
            throw new LtiException($this->ltiLog->msg(
                'Unable to get access token: '.$resp->body()));

        // make sure the response has the parameters we need
        try {
            $checker = new ParamChecker($resp->json(), $this->ltiLog);
            $checker->requireParams([self::ACCESS_TOKEN, self::EXPIRES_IN]);
            if (!is_numeric($resp[self::EXPIRES_IN]))
                throw new LtiException($this->ltiLog->msg(
                    'expires_in must be a number'));
        }
        catch(LtiException $e) {
            throw new LtiException($this->ltiLog->msg(
                "Invalid access token response: " . $e->getMessage()), 0, $e);
        }

        // store access token in cache
        $token = $resp[self::ACCESS_TOKEN];
        // in case the request took some time and we want to make sure we expire
        // the token before it becomes invalid
        $expires = $resp[self::EXPIRES_IN] - $timeTaken - 1;
        // only store the token if it's valid for a minimum time
        if ($expires > self::MINIMUM_TOKEN_VALID_TIME) {
            $store->put($cacheKey, $token, $expires);
        }

        return $token;
    }

    /**
     * Create a unique cache key based on the platform id and the access token
     * scope. Since the key can only be max 255 chars, we can't exactly use
     * the scope uri as is. PlatformAccessToken has a list of valid scopes that
     * we accept, each scope is mapped to an int, we can use that as an id,
     * shortening the scope to fit the character limit.
     *
     * This is basically a comma delimited id list, with the platform id first
     * and the scope ids following sequentially.
     */
    private function getCacheKey(int $platformId, array $scopes): string
    {
        $key = $platformId . ',';
        foreach ($scopes as $scope) {
            $key .= self::$VALID_SCOPES[$scope] . ',';
        }
        return $key;
    }

    /**
     * Build the JWT needed to make the access token request.
     */
    private function getRequestJwt(
        Platform $platform,
        Tool $tool,
        array $scopes
    ): string {
        $ownTool = Tool::getOwnTool();
        $key = $ownTool->keys()->first();
        $time = time();
        $platformClient = $tool->getPlatformClient($platform->id);
        if (!$platformClient) throw new LtiException($this->ltiLog->msg(
                                                     'Unregistered client'));
        return Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->iss($platformClient->client_id)
            ->sub($platformClient->client_id)
            // the audience is often just the token endpoint url
            ->aud($platform->access_token_url)
            ->iat($time) // automatically set issued at time
            ->exp($time + self::REQUEST_EXPIRY_TIME)
            ->jti(Nonce::create(self::REQUEST_EXPIRY_TIME))
            ->header(Param::KID, $key->kid)
            ->sign($key->key);
    }

    /**
     * Throw an exception if it's a scope we don't support.
     */
    private function checkScopes(array $scopes)
    {
        if (!$scopes)
            throw new LtiException($this->ltiLog->msg(
                "Access token request scope can't be empty"));
        foreach ($scopes as $scope) {
            if (!array_key_exists($scope, self::$VALID_SCOPES)) {
                throw new LtiException($this->ltiLog->msg(
                    'Unsupported scope: ' . $scope));
            }
        }
    }

    /**
     * Laravel has a convenient function to get the access token for us, in the
     * form of request->bearerToken(). Unfortunately, it does not follow spec
     * and requires bearer to be case sensitive, e.g. it works with:
     *   "authorization: Bearer <access token>"
     * But not with:
     *   "authorization: bearer <access token>"
     *
     * So we have to have our own function to get the access token.
     */
    public static function fromRequestHeader(
        Request $request,
        LtiLog $ltiLog
    ): string {
        $authHeader = $request->header('authorization');
        if (!$authHeader) {
            throw new LtiException($ltiLog->msg(
                'Missing authorization header', $request));
        }
        $ltiLog->debug("Authorization: $authHeader", $request);
        // make sure we have a bearer token, no matter how it's capitalized
        $tokenType = substr($authHeader, 0, 6);
        if (strcasecmp(Param::TOKEN_TYPE_VALUE, $tokenType) != 0) {
            throw new LtiException($ltiLog->msg(
                'Unknown authorization token type: ' . $tokenType, $request));
        }
        return substr($authHeader, 7);
    }
}
