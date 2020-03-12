<?php

namespace App\Models;

use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;

class LtiFakeUser extends Model
{
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }

    public function lti_real_user()
    {
        return $this->belongsTo('App\Models\LtiRealUser');
    }

    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->login_hint = $faker->uuid;
        $this->name = $faker->name;
        $this->email = $faker->email;
        $this->save();
    }
}
