<?php
namespace UBC\LTI\Specs;

use Illuminate\Support\Facades\Log;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Serializer\CompactSerializer;

use UBC\LTI\LTIException;
use UBC\LTI\Param;

// the JWT framework only offers easy access to the JWS claims *after* it has
// been verified, but sometimes, we need access to the claims in order to
// perform the verification. This class helps in such cases.
class JwsUtil
{

    private JWS $jws;
    private array $claims;

    public function __construct(string $jwtString)
    {
        try {
            $this->jws = (new CompactSerializer())->unserialize($jwtString);
        }
        catch (\Exception $e) {
            throw new LTIException(
                'Unable to deserialized JWT: ' . $e->getMessage(),
                0,
                $e
            );
        }
        $this->claims = json_decode($this->jws->getPayload(), true);
    }

    // returns the given claim's value if it exists, throws an exception if it
    // doesn't. It's throwing an exception cause a claim that we want to access
    // before verification is probably required anyways.
    public function getClaim($claim): string
    {
        if (isset($this->claims[$claim])) {
            return $this->claims[$claim];
        }
        throw new LTIException("JWS missing param '$claim'.");
    }

    // returns key id if there is one, empty string otherwise
    // kid can't be treated as a claim because it's part of the signature header
    // kid is also an optional parameter so shouldn't throw exception
    public function getKid(): string
    {
        if (
            isset($this->jws->getSignature(0)->getProtectedHeader()[Param::KID])
        ) {
            return $this->jws->getSignature(0)->getProtectedHeader()[Param::KID];
        }
        return '';
    }
}
