<?php

namespace App\Models;

use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;

class LtiUser extends Model
{
    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }

    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->fake_login_hint = $faker->uuid;
        $this->fake_name = $faker->name;
        $this->fake_email = $faker->email;
        $this->save();
    }
}
