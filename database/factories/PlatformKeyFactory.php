<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\PlatformKey;

class PlatformKeyFactory extends Factory
{
    protected $model = PlatformKey::class;

    public function definition()
    {
        // WARNING: do not use for real, this key contains BOTH public &
        // private keys, this is so that we can write test cases easier, since
        // we need both sides of key to generate and validate signatures
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
            'kid' => $this->faker->name,
            'key' =>json_encode($key)
        ];
    }
}
