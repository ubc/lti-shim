<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Tool;
use App\Models\LtiSession;


$factory->define(LtiSession::class, function (Faker $faker) {
    return [
        'log_stream' => $faker->word
    ];
});
