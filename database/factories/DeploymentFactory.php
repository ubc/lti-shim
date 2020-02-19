<?php
use Faker\Generator as Faker;

use App\Models\Deployment;

$factory->define(Deployment::class, function (Faker $faker) {
    return [
        'lti_deployment_id' => $faker->uuid
    ];
});

