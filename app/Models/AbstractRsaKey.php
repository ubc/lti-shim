<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Jose\Component\Core\JWK;

abstract class AbstractRsaKey extends Model
{
    // lets JwksUpdater to mass assign these fields, I'm not sure what would
    // happen if this was overridden in the child classes, might have to specify
    // the same thing
    protected $fillable = ['kid', 'key'];

    // because the spec is wishy washy on key distribution, the stored key
    // in here might contain both public and private keys, use the
    // public_key accessor if you only want the public part
    public function getKeyAttribute(string $key): JWK
    {
        return JWK::createFromJson($key);
    }

    // filter out the private key if its in there
    public function getPublicKeyAttribute(): JWK
    {
        return $this->key->toPublic();
    }
}
