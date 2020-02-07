<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Jose\Component\Core\JWK;

class PlatformKey extends Model
{
    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    public function getPublicKeyAttribute($key)
    {
        return JWK::createFromJson($key);
    }
}
