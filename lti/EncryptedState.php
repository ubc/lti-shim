<?php
namespace UBC\LTI;

use Illuminate\Support\Facades\Log;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;

use UBC\LTI\LtiException;
use UBC\LTI\Param;

class EncryptedState
{
    public static function encrypt(array $claims): string
    {
        $time = time();
        $jwe = Build::jwe() // We build a JWE
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->alg(Param::RSA_OAEP_256) // key encryption alg
            ->enc(Param::A256GCM) // content encryption alg
            // compression not necessary for our current use case, small data
            //->zip(Param::ZIP_ALG) // compress the data, DEFLATE alg
            ->crit(['alg', 'enc']); // We mark some header parameters as critical
        foreach ($claims as $key => $val) {
            $jwe = $jwe->claim($key, $val);
        }
        // encrypt with the public key
        $jwe = $jwe->encrypt(EncryptionKey::getNewestKey()->public_key);
        return $jwe;
    }

    public static function decrypt(string $token): JWT
    {
        try {
            $jwt = Load::jwe($token) // deserialize the token
                ->algs([Param::RSA_OAEP_256]) // key encryption algo
                ->encs([Param::A256GCM]) // content encryption algo
                ->exp()
                ->iat()
                ->nbf()
                ->key(EncryptionKey::getNewestKey()->key) // private key decrypt
                ->run();
            return $jwt;
        }
        catch(\Exception $e) {
            throw new LtiException('Unable to decrypt encrypted state.', 0, $e);
        }
    }
}
