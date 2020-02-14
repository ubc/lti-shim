<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Jose\Component\Core\JWK;

use App\Models\AbstractRsaKey;

class ToolKey extends AbstractRsaKey
{
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
