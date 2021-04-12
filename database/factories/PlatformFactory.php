<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

use App\Models\Platform;
use App\Models\PlatformClient;
use App\Models\PlatformKey;

class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    public function configure()
    {
        return $this->afterCreating(function(Platform $platform) {
            // each platform needs a public key
            $platform->keys()
                     ->save(PlatformKey::factory()
                                     ->make(['platform_id' => $platform->id]));
        });
    }

    public function definition()
    {
        $domain = $this->faker->domainWord;
        $domain = $domain . '.' . $this->faker->safeEmailDomain();
        return [
            'name' => $domain,
            'iss' => 'https://' . $domain,
            'auth_req_url' => 'https://' . $domain . '/lti/auth'
        ];
    }
}
