<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Platform;
use App\Models\PlatformKey;


$factory->define(Platform::class, function (Faker $faker) {
    $domain = $faker->domainName;
    return [
        'name' => $domain,
        'iss' => 'https://' . $domain,
        'auth_req_url' => 'https://' . $domain . '/lti/auth',
        'shim_client_id' => $faker->uuid
    ];
});
$factory->afterCreating(
    Platform::class,
    function($platform, Faker $faker) {
        // each platform also needs a public key
        $platform->keys()
                 ->save(
                     factory(PlatformKey::class)
                         ->create(['platform_id' => $platform->id])
                 );
    }
);
