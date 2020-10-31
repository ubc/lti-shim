<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\PlatformClient;

class PlatformClientFactory extends Factory
{
    protected $model = PlatformClient::class;

    public function definition()
    {
        return [
            'client_id' => $this->faker->uuid
        ];
    }
}
