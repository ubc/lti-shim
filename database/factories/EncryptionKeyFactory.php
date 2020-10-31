<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\EncryptionKey;

class EncryptionKeyFactory extends Factory
{
    protected $model = EncryptionKey::class;

    public function definition()
    {
        // different from the keys used for signatures
        $key = JWKFactory::createRSAKey(
            4096,
            [
                'alg' => 'RSA-OAEP-256', // JWE compatible RSA-OAEP-256
                'use' => 'enc' // used for encryption/decryption operations only
            ]
        );
        return [
            'key' =>json_encode($key)
        ];
    }
}
