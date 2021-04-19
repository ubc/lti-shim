<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\LtiSession;

class LtiSessionFactory extends Factory
{
    protected $model = LtiSession::class;

    public function definition()
    {
        return [
            'token' => [],
            'log_stream' => $this->faker->word
        ];
    }
}
