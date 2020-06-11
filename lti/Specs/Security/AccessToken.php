<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Log;

use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;
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
}
