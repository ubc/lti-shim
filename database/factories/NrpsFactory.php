<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Nrps;
use App\Models\NrpsKey;


$factory->define(Nrps::class, function (Faker $faker) {
    $domain = $faker->domainName;
    return [
        'context_memberships_url' => $faker->url
    ];
});
