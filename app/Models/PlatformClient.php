<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformClient extends Model
{
    protected $fillable = ['client_id'];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }
}
