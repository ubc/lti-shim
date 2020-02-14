<?php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

use App\Models\Tool;
use App\Models\ToolKey;


$factory->define(Tool::class, function (Faker $faker) {
    $domain = $faker->domainName;
    return [
        'name' => $domain,
        'client_id' => $faker->uuid,
        'target_link_uri' => 'https://' . $domain . '/target'
    ];
});

$factory->afterCreating(
    Tool::class,
    function($tool, Faker $faker) {
        // each tool also needs a public key
        $tool->keys()
             ->save(factory(ToolKey::class)->create(['tool_id' => $tool->id]));
    }
);