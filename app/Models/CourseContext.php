<?php

namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Model;

class CourseContext extends Model
{
    protected $fillable = ['real_context_id', 'deployment_id'];

    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->fake_context_id = $faker->sha256;
        $this->save();
    }
}
