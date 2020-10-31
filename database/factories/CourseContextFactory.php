<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\CourseContext;

class CourseContextFactory extends Factory
{
    protected $model = CourseContext::class;

    public function definition()
    {
        return [
            'real_context_id' => $this->faker->uuid,
            'fake_context_id' => $this->faker->sha256,
        ];
    }
}
