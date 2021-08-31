<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\LtiFakeUser;

class LtiFakeUserFactory extends Factory
{
    protected $model = LtiFakeUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'email' => $this->faker->email,
            'sub' => $this->faker->uuid,
        ];
    }
}
