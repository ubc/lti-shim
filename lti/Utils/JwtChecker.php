<?php
namespace UBC\LTI\Utils;

use UBC\LTI\Specs\Security\Nonce;
use UBC\LTI\Utils\Param;

/**
 * Does basic security checks.
 */
class JwtChecker
{
    private array $payload = [];

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function checkAll(): void
    {
        // protect against expired token and such
        $this->checkTimestamps();
        // protect against replayed token, not always necessary such as when
        // we're using as access token
        $this->checkNonce();
    }

    /**
     * Check to make sure that the nonce hasn't already been used.
     *
     * @throw LtiException if nonce is invalid
     */
    public function checkNonce(): void
    {
        $nonce = $this->payload[Param::JTI] ?? null;
        if (!empty($nonce) && Nonce::isValid($nonce))
            Nonce::used($nonce);
        else
            throw new LtiException('Invalid nonce "' . $nonce . '"');
    }

    /**
     * The JWT library we're using does not support floating point for exp,
     * nbf, iat claims. Even though the spec allows them. So for JWTs that
     * use floats for those numbers, we need to do custom validation for them.
     *
     * Note that exp and iat are required by LTI, nbf is optional.
     *
     * @throw LtiException if any of the timestamps are invalid
     */
    public function checkTimestamps(): void
    {
        $now = time();
        $exp = $this->payload[Param::EXP] ?? null;
        if (!(is_int($exp) || is_float($exp))) {
            throw new LtiException('exp must exist and be numeric');
        }
        if ($now > $exp + Param::TOKEN_LEEWAY) {
            throw new LtiException('Expired JWT');
        }

        // Not valid if received before the iat (issued at) time
        $iat = $this->payload[Param::IAT] ?? null;
        if (!(is_int($iat) || is_float($iat))) {
            throw new LtiException('iat must exist and be numeric');
        }
        if ($iat > ($now + Param::TOKEN_LEEWAY)) {
            throw new LtiException('Back to the future JWT');
        }

        // Not valid if it's too old. We got an old token with a valid
        // (but excessively large) expiration window that we'd prefer not to
        // validate.
        if (($now - $iat) > Param::TOKEN_OLD_AGE) {
            throw new LtiException('JWT too old');
        }

        // Not valid if received before the nbf (not before) time
        $nbf = $this->payload[Param::NBF] ?? null;
        if ($nbf) {
            if (!(is_int($nbf) || is_float($nbf))) {
                throw new LtiException('nbf must be numeric');
            }
            if ($nbf > ($now + Param::TOKEN_LEEWAY)) {
                throw new LtiException('JWT not yet nbf');
            }
        }
    }
}
