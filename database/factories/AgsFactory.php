<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\Ags;

class AgsFactory extends Factory
{
    protected $model = Ags::class;

    public function definition()
    {
        return [
            'lineitems' => $this->faker->url
        ];
    }
}
