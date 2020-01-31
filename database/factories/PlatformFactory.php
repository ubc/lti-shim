<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Platform;


$factory->define(Platform::class, function (Faker $faker) {
    $domain = $faker->domainName;
    return [
        'name' => $domain,
        'iss' => 'https://' . $domain,
        'auth_req_url' => 'https://' . $domain . '/lti/auth'
    ];
});
