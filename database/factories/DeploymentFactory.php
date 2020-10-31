<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Deployment;

class DeploymentFactory extends Factory
{
    protected $model = Deployment::class;

    public function definition()
    {
        return [
            'lti_deployment_id' => $this->faker->uuid
        ];
    }
}
