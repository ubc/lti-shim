<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Jose\Component\Core\JWK;

use App\Models\AbstractRsaKey;

class PlatformKey extends AbstractRsaKey
{
    use HasFactory;

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }
}
