<?php

use Faker\Generator as Faker;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\EncryptionKey;

$factory->define(EncryptionKey::class, function (Faker $faker) {
    // different from the keys used for signatures
    $key = JWKFactory::createRSAKey(
        4096,
        [
            'alg' => 'RSA-OAEP-256', // JWE compatible RSA-OAEP-256
            'use' => 'enc'    // used for encryption/decryption operations only
        ]
    );
    return [
        'key' =>json_encode($key)
    ];
});
