<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\Nrps;

class NrpsFactory extends Factory
{
    protected $model = Nrps::class;

    public function definition()
    {
        return [
            'context_memberships_url' => $this->faker->url
        ];
    }
}
