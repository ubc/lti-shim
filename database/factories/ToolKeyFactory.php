<?php

use Faker\Generator as Faker;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\ToolKey;

$factory->define(ToolKey::class, function (Faker $faker) {
    // WARNING: do not use for real, this key contains BOTH public & private
    // keys, this is so that we can write test cases easier, since we need both
    // sides of key to generate and validate signatures
    $key = JWKFactory::createRSAKey(
        2048,
        [
            'alg' => 'RS256',
            'use' => 'sig',
            'key_ops' => ['sign', 'verify'],
            'kty' => 'RSA'
        ]
    );
    return [
        'kid' => $faker->name,
        'key' =>json_encode($key)
    ];
});

