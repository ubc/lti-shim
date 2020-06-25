<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class Nrps extends Model
{
    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
        
}
