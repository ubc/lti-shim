<?php

use Faker\Generator as Faker;

use App\Models\LtiFakeUser;

$factory->define(LtiFakeUser::class, function (Faker $faker) {
    return [
        'login_hint' => $faker->uuid,
        'name' => $faker->name,
        'email' => $faker->email
    ];
});
