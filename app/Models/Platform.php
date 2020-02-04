<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    public function clients()
    {
        return $this->hasMany('App\Models\PlatformClient');
    }

    public function deployments()
    {
        return $this->hasMany('App\Models\Deployment');
    }
}
