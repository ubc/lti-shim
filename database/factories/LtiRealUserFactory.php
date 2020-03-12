<?php

use Faker\Generator as Faker;

use App\Models\LtiRealUser;

$factory->define(LtiRealUser::class, function (Faker $faker) {
    return [
        'login_hint' => $faker->uuid,
        'name' => $faker->name,
        'email' => $faker->email,
        'sub' => $faker->sha1
    ];
});
