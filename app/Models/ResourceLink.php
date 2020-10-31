<?php

namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceLink extends Model
{
    use HasFactory;

    protected $fillable = ['real_link_id', 'deployment_id'];

    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->fake_link_id = $faker->sha256;
        $this->save();
    }
}
