<?php

use Faker\Generator as Faker;

use App\Models\CourseContext;

$factory->define(CourseContext::class, function (Faker $faker) {
    return [
        'real_context_id' => $faker->uuid,
        'fake_context_id' => $faker->sha256,
    ];
});
