<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Jose\Component\KeyManagement\JWKFactory;

use App\Models\ToolKey;

class ToolKeyFactory extends Factory
{
    protected $model = ToolKey::class;

    public function definition()
    {
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
