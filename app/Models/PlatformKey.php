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

    // because the spec is wishy washy on key distribution, the stored key
    // in here might contain both public and private keys, use the
    // public_key accessor if you only want the public part
    public function getKeyAttribute($key)
    {
        return JWK::createFromJson($key);
    }

    // filter out the private key if its in there
    public function getPublicKeyAttribute()
    {
        return $this->key->toPublic();
    }
}
