<?php
use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\PlatformClient;

// don't use this factory directly, call the Platform factory to create a
// platform as well as an associated PlatformClient
$factory->define(
    PlatformClient::class,
    function (Faker $faker) {
        return [
            'client_id' => $faker->uuid
        ];
    }
);

