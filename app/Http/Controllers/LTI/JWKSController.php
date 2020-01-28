<?php

namespace App\Http\Controllers\LTI;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Jose\Component\KeyManagement\JWKFactory;

use App\Http\Controllers\Controller;

use UBC\LTI\KeyStorage;

class JWKSController extends Controller
{
    /**
     * Serves up a list of public keys as a JSON Web Key Set as defined by
     * RFC7517. The public keys are identified by a key ID param called 'kid' and
     * are used by 3rd parties to verify the LTI JWT we've signed.
     *
     * @param Request $request
     */
    public function jwks(Request $request)
    {
        return array('keys' => array(KeyStorage::getMyPublicKey()->all()));
    }

    /**
     * For reference only, how to generate a public/private key pair
     */
    public function keygen(Request $request)
    {
        $key = JWKFactory::createRSAKey(
            4096, // Size in bits of the key. We recommend at least 2048 bits.
            [
                'alg' => 'RS256',
                'use' => 'sig',
                'key_ops' => ['sign', 'verify'],
                'kty' => 'RSA'
            ]);
        Log::debug("Public Only");
        Log::debug(json_encode($key->toPublic(), JSON_PRETTY_PRINT));
        Log::debug("Public & Private");
        Log::debug(json_encode($key, JSON_PRETTY_PRINT));
    }
}

