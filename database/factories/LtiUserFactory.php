<?php

use Faker\Generator as Faker;

use App\Models\LtiUser;

$factory->define(LtiUser::class, function (Faker $faker) {
    return [
        'real_login_hint' => $faker->uuid,
        'real_name' => $faker->name,
        'real_email' => $faker->email,
        'fake_login_hint' => $faker->uuid,
        'fake_name' => $faker->name,
        'fake_email' => $faker->email,
        'sub' => $faker->sha1
    ];
});
