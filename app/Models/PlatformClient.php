<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformClient extends Model
{
    public function platform()
    {
        return $this->belongsTo('App\Model\Platform');
    }
}
