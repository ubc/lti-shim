<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\ReturnUrl;

class ReturnUrlFactory extends Factory
{
    protected $model = ReturnUrl::class;

    public function definition()
    {
        return [
            'url' => $this->faker->url,
            'token' => $this->faker->randomLetter . $this->faker->randomLetter .
                $this->faker->randomLetter . $this->faker->randomLetter .
                $this->faker->randomLetter . $this->faker->randomLetter .
                $this->faker->randomLetter . $this->faker->randomLetter .
                $this->faker->randomLetter . $this->faker->randomLetter .
                $this->faker->randomLetter . $this->faker->randomLetter
        ];
    }
}
