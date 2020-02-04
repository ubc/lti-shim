<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
