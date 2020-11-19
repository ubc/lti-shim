<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\AgsResult;

class AgsResultFactory extends Factory
{
    protected $model = AgsResult::class;

    public function definition()
    {
        return [
            'result' => $this->faker->url
        ];
    }
}
