<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\AbstractRsaKey;

class EncryptionKey extends AbstractRsaKey
{
    // if we always get the newest key, this lets us do periodic key rotation
    // by just adding a new key in
    public static function getNewestKey() : self
    {
        $key = self::latest('id')->first();
        if (!$key)
            throw new \UnexpectedValueException('No encryption keys, please generate one!');
        return $key;
    }
}
