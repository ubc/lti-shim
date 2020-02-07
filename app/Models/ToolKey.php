<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Tool;

class ToolKey extends Model
{
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
