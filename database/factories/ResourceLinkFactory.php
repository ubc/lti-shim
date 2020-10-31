<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\ResourceLink;

class ResourceLinkFactory extends Factory
{
    protected $model = ResourceLink::class;

    public function definition()
    {
        return [
            'real_link_id' => $this->faker->uuid,
            'fake_link_id' => $this->faker->sha256,
        ];
    }
}
