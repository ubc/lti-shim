<?php
namespace UBC\LTI\Utils;

use Illuminate\Support\Facades\Log;

use App\Models\EncryptionKey;

use UBC\LTI\Specs\Security\Nonce;
use UBC\LTI\Utils\JweUtil;
use UBC\LTI\Utils\JwtChecker;
use UBC\LTI\Utils\LtiException;
use UBC\LTI\Utils\Param;

class EncryptedState
{
    public static function encrypt(array $claims): string
    {
        $time = time();
        $payload = [
            Param::EXP => $time + Param::EXP_TIME,
            Param::IAT => $time,
            Param::NBF => $time,
            Param::JTI => Nonce::create(),
        ];
        foreach ($claims as $key => $val) {
            $payload[$key] = $val;
        }
        return JweUtil::build($payload);
    }

    public static function decrypt(string $token): array
    {
        try {
            $payload = JweUtil::decrypt($token);
            $checker = new JwtChecker($payload);
            $checker->checkAll();
            return $payload;
        }
        catch(\Exception $e) {
            throw new LtiException('Unable to decrypt encrypted state.', 0, $e);
        }
    }
}
