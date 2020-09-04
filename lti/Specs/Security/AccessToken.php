<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\LtiException;
use UBC\LTI\Param;
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
    // each scope must be mapped to an unique int as we're using it as a unique
    // id for that scope in access token cache
    public const VALID_SCOPES = [
        Param::NRPS_SCOPE_URI => 0
    ];

    // when we request an access token, the response must have these parameters
    private const ACCESS_TOKEN = 'access_token'; // the actual access token
    private const EXPIRES_IN = 'expires_in'; // how long the token is valid for
                                            // in seconds


    public static function create(Tool $tool, array $scopes): string
    {
        self::checkScopes($scopes);
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
        $jwe->claim(Param::SCOPE, $scopes);
        // encrypt with the public key
        $jwe = $jwe->encrypt(EncryptionKey::getNewestKey()->public_key);
        return $jwe;
    }

    public static function verify(string $token): JWT
    {
        // TODO enforce scope checking once we have more than 1 scope
        try {
            $jwt = Load::jwe($token) // deserialize the token
                ->algs([Param::RSA_OAEP_256]) // key encryption algo
                ->encs([Param::A256GCM]) // content encryption algo
                ->exp()
                ->iat()
                ->key(EncryptionKey::getNewestKey()->key) // private key decrypt
                ->run();
            return $jwt;
        }
        catch(\Exception $e) {
            Log::error("Unable to verify access token.");
            throw new LtiException('Invalid access token: ' . $e->getMessage(),
                                   0, $e);
        }
    }

    /**
     * Shim requesting an access token from a platform. We want to cache the
     * access token so that we don't have to send an access token request for
     * every service call.
     */
    public static function request(
        Platform $platform,
        Tool $tool,
        array $scopes
    ): string {
        self::checkScopes($scopes);

        // see if we can get from cache
        $store = Cache::store(self::ACCESS_TOKEN_STORE);
        $cacheKey = self::getCacheKey($platform->id, $scopes);
        $token = $store->get($cacheKey);
        if ($token) return $token;

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
            throw new LtiException('Unable to get access token: '.$resp->body());

        // make sure the response has the parameters we need
        try {
            $checker = new ParamChecker($resp->json());
            $checker->requireParams([self::ACCESS_TOKEN, self::EXPIRES_IN]);
            if (!is_numeric($resp[self::EXPIRES_IN]))
                throw new LtiException('expires_in must be a number');
        }
        catch(LtiException $e) {
            throw new LtiException("Invalid access token response: " .
                $e->getMessage(), 0, $e);
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
    private static function getCacheKey(int $platformId, array $scopes): string
    {
        $key = $platformId . ',';
        foreach ($scopes as $scope) {
            $key .= self::VALID_SCOPES[$scope] . ',';
        }
        return $key;
    }

    /**
     * Build the JWT needed to make the access token request.
     */
    private static function getRequestJwt(
        Platform $platform,
        Tool $tool,
        array $scopes
    ): string {
        $ownTool = Tool::getOwnTool();
        $key = $ownTool->keys()->first();
        $time = time();
        $platformClient = $tool->getPlatformClient($platform->id);
        if (!$platformClient) throw new LtiException('Unregistered client');
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
    private static function checkScopes(array $scopes)
    {
        if (!$scopes)
            throw new LtiException("Access token request scope can't be empty");
        foreach ($scopes as $scope) {
            if (!array_key_exists($scope, self::VALID_SCOPES)) {
                throw new LtiException('Unsupported scope: ' . $scope);
            }
        }
    }
}
