<?php

namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    protected $fillable = ['lti_deployment_id', 'platform_id', 'tool_id'];
    protected $with = ['platform'];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    // auto-populate fake_lti_deployment_id if it's not filled
    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->fake_lti_deployment_id = $faker->uuid;
        $this->save();
    }
}
