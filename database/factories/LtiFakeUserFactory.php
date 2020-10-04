<?php

use Faker\Generator as Faker;

use App\Models\LtiFakeUser;

$factory->define(LtiFakeUser::class, function (Faker $faker) {
    return [
        'login_hint' => $faker->uuid,
        'name' => $faker->firstName . ' ' . $faker->lastName,
        'email' => $faker->email,
        'sub' => $faker->uuid,
    ];
});
