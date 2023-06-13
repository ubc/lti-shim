<?php
namespace UBC\LTI\Utils;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;

use App\Models\AbstractRsaKey;

use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

/**
 * Hides the boiler plate required by JWT Framework to build and load JWSs.
 */
class JwsUtil
{
    public static function build(array $payload, AbstractRsaKey $key): string
    {
        $sigAlgo = new AlgorithmManager([new RS256()]);
        $builder = new JWSBuilder($sigAlgo);
        $jws = $builder->create()
                       ->withPayload(json_encode($payload))
                       ->addSignature($key->key, [Param::ALG => Param::RS256,
                                                  Param::KID => $key->kid])
                       ->build();
        $serializer = new CompactSerializer();
        return $serializer->serialize($jws, 0);
    }

    /**
     * Also verifies the JWS signature.
     */
    public static function load(string $token, AbstractRsaKey $key): array
    {
        $sigAlgo = new AlgorithmManager([new RS256()]);
        $verifier = new JWSVerifier($sigAlgo);
        $serializer = new JWSSerializerManager([new CompactSerializer()]);
        $jws = $serializer->unserialize($token);
        $isVerified = $verifier->verifyWithKey($jws, $key->key, 0);
        if ($isVerified) return json_decode($jws->getPayload(), true);
        throw new LtiException('Failed JWS Signature Verification');
    }
}
