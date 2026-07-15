<?php
namespace UBC\LTI\Utils;

use Illuminate\Support\Facades\Log;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Encryption\Algorithm\KeyEncryption\RSAOAEP256;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Encryption\JWETokenSupport;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\Serializer\JWESerializerManager;

use App\Models\EncryptionKey;

use UBC\LTI\Utils\Param;

/**
 * Basically just hides the boiler plate required by the JWT Framework to
 * handle JWEs.
 */
class JweUtil
{
    public static function build(array $payload): string
    {
        $keyEncryptAlgo = new AlgorithmManager([new RSAOAEP256()]);
        $contentEncryptAlgo = new AlgorithmManager([new A256GCM()]);
        $compressionMethod = new CompressionMethodManager([new Deflate()]);
        $builder = new JWEBuilder($keyEncryptAlgo, $contentEncryptAlgo,
                                  $compressionMethod);
        $jwe = $builder->create()
                       ->withPayload(json_encode($payload))
                       ->withSharedProtectedHeader([
                           Param::ALG => Param::RSA_OAEP_256,
                           Param::ENC => Param::A256GCM,
                           Param::ZIP => Param::ZIP_ALG
                       ])
                       ->addRecipient(EncryptionKey::getNewestKey()->public_key)
                       ->build();
        $serializer = new CompactSerializer();
        return $serializer->serialize($jwe, 0);
    }

    /**
     * It is expected that callers be ready to handle exceptions from this.
     */
    public static function decrypt(string $token): array
    {
        $keyEncryptAlgo = new AlgorithmManager([new RSAOAEP256()]);
        $contentEncryptAlgo = new AlgorithmManager([new A256GCM()]);
        $compressionMethod = new CompressionMethodManager([new Deflate()]);
        $serializer = new JWESerializerManager([new CompactSerializer()]);
        $decrypter = new JWEDecrypter($keyEncryptAlgo, $contentEncryptAlgo,
                                      $compressionMethod);
        $headerChecker = new HeaderCheckerManager(
            [new AlgorithmChecker(['RSA-OAEP-256'])],
            [new JWETokenSupport()]
        );
        $loader = new JWELoader($serializer, $decrypter, $headerChecker);
        $key = EncryptionKey::getNewestKey()->key;
        $recipientKey; // unused, but required as param to loadAndDecryptWithKey
        $jwe = $loader->loadAndDecryptWithKey($token, $key, $recipientKey);
        return json_decode($jwe->getPayload(), true);
    }
}
