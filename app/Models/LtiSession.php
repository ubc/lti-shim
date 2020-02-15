<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LtiSession extends Model
{
    // need to tell Laravel to auto decode our JSON column
    protected $casts = [
        'session' => 'array'
    ];
}
