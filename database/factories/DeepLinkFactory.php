<?php

namespace Database\Factories;

use App\Models\DeepLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeepLinkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DeepLink::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'url' => $this->faker->url
        ];
    }
}
