<?php

namespace App\Models;

use Faker\Factory as Faker;
use Illuminate\Support\Facades\Log;
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
        $email = $faker->email;
        $count = 0;
        // try to reasonably ensure a unique fake email
        while (self::where('email', $email)->exists() && $count <= 10) {
            $email = $faker->email;
            $count++;
        }
        $this->email = $email;
        $this->save();
    }
}
