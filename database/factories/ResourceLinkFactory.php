<?php

use Faker\Generator as Faker;

use App\Models\ResourceLink;

$factory->define(ResourceLink::class, function (Faker $faker) {
    return [
        'real_link_id' => $faker->uuid,
        'fake_link_id' => $faker->sha256,
    ];
});
