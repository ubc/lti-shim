<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Nrps;


$factory->define(Nrps::class, function (Faker $faker) {
    return [
        'context_memberships_url' => $faker->url
    ];
});
