<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;
use App\Models\Platform;
use App\Models\Tool;

use UBC\LTI\LTIException;
use UBC\LTI\Param;

class AccessToken
{
    // IMS recommends tokens be valid for 1 hour (3600 seconds)
    public const EXPIRY_TIME = 3600;

    public static function create(Tool $tool, array $scopes): string
    {
        $time = time();
        // IETF has a draft spec for JWT access tokens that we're using as guide
        $jwe = Build::jwe() // We build a JWE
            ->typ(Param::AT_JWT)
            ->iss(config('lti.iss'))
            ->exp($time + self::EXPIRY_TIME)
            ->iat($time)
            ->aud(config('lti.iss'))
            ->sub($tool->client_id)
            ->jti('TODO token')
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
            throw new LTIException('Failed to verify token.', 0, $e);
        }
    }

    // shim requesting an access token from a platform
    public static function request(Platform $platform, array $scopes): string
    {
        Log::debug('====================================================');
        $ownTool = Tool::getOwnTool();
        $key = $ownTool->keys()->first();
        $time = time();
        $requestJwt = Build::jws()
            ->typ(Param::JWT)
            ->alg(Param::RS256)
            ->iss($ownTool->iss)
            ->sub($platform->shim_client_id)
            // the audience is often just the token endpoint url
            ->aud($platform->oauth_token_url)
            ->iat($time) // automatically set issued at time
            ->exp($time + 60)
            // TODO: real JTI protection
            ->jti('JWT Token Identifier1')
            ->header(Param::KID, $key->kid)
            ->sign($key->key);
        Log::debug($requestJwt);
        $params = [
            Param::GRANT_TYPE => Param::GRANT_TYPE_VALUE,
            Param::CLIENT_ASSERTION_TYPE => Param::CLIENT_ASSERTION_TYPE_VALUE,
            Param::CLIENT_ASSERTION => $requestJwt,
            Param::SCOPE => implode(' ', $scopes)
        ];
        Log::debug($params);
        $resp = Http::asForm()->post($platform->oauth_token_url, $params);
        Log::debug($resp->body());
        if ($resp->failed())
            throw new LTIException('Unable to get access token: '.$resp->body());
        Log::debug($resp->body());
        return $resp['access_token'];
    }
}
