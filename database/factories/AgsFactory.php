<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Ags;


$factory->define(Ags::class, function (Faker $faker) {
    return [
        'lineitems' => $faker->url
    ];
});
