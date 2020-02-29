<?php
namespace UBC\LTI;

use Illuminate\Support\Facades\Log;

use Jose\Component\Core\JWK;
use Jose\Easy\Build;
use Jose\Easy\JWT;
use Jose\Easy\Load;

use App\Models\EncryptionKey;

use UBC\LTI\LTIException;

class EncryptedState
{
    public static function encrypt(array $claims): string
    {
        $time = time();
        $jwe = Build::jwe() // We build a JWE
            ->exp($time + 3600)
            ->iat($time)
            ->nbf($time)
            ->alg('RSA-OAEP-256') // key encryption alg
            ->enc('A256GCM') // content encryption alg
            // compression not necessary for our current use case, small data
            //->zip('DEF') // compress the data, DEFLATE alg
            ->crit(['alg', 'enc']); // We mark some header parameters as critical
        foreach ($claims as $key => $val) {
            $jwe = $jwe->claim($key, $val);
        }
        // encrypt with the public key
        $jwe = $jwe->encrypt(self::getKey()->public_key);
        return $jwe;
    }

    public static function decrypt(string $token): JWT
    {
        try {
            $jwt = Load::jwe($token) // deserialize the token
                ->algs(['RSA-OAEP-256']) // key encryption algo
                ->encs(['A256GCM']) // content encryption algo
                ->exp()
                ->iat()
                ->nbf()
                ->key(self::getKey()->key) // decrypt using private key
                ->run();
            return $jwt;
        }
        catch(\Exception $e) {
            throw new LTIException('Unable to decrypt encrypted state.', 0, $e);
        }
    }

    private static function getKey()
    {
        // we want to get the newest key, this lets us do periodic key rotation
        $key = EncryptionKey::latest('id')->first();
        if (!$key)
            throw new \UnexpectedValueException('No encryption keys, please generate one!');
        return $key;
    }
}
