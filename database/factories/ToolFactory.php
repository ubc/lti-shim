<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;

use App\Models\Tool;
use App\Models\ToolKey;


class ToolFactory extends Factory
{
    protected $model = Tool::class;

    public function configure()
    {
        return $this->afterCreating(function(Tool $tool) {
            // each tool also needs a public key
            $tool->keys()
                 ->save(ToolKey::factory()->make(['tool_id' => $tool->id]));
        });
    }

    public function definition()
    {
        $domain = $this->faker->domainName;
        return [
            'name' => $domain,
            'client_id' => $this->faker->uuid,
            'oidc_login_url' => 'https://' . $domain . '/lti/login',
            'auth_resp_url' => 'https://' . $domain . '/lti/resp',
            'target_link_uri' => 'https://' . $domain . '/target'
        ];
    }
}
