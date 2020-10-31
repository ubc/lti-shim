<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\LtiRealUser;

class LtiRealUserFactory extends Factory
{
    protected $model = LtiRealUser::class;

    public function definition()
    {
        return [
            'login_hint' => $this->faker->uuid,
            'name' =>       $this->faker->name,
            'email' =>      $this->faker->email,
            'sub' =>        $this->faker->sha1
        ];
    }
}
