<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Tool;


$factory->define(Tool::class, function (Faker $faker) {
    $domain = $faker->domainName;
    return [
        'name' => $domain,
        'client_id' => $faker->uuid,
        'target_link_uri' => 'https://' . $domain . '/target'
    ];
});
