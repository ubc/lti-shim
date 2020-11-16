<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\AgsLineitem;

class AgsLineitemFactory extends Factory
{
    protected $model = AgsLineitem::class;

    public function definition()
    {
        return [
            'lineitem' => $this->faker->url
        ];
    }
}
