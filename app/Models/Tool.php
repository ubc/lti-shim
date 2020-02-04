<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    public function deployments()
    {
        return $this->hasMany('App\Models\Deployment');
    }
}
