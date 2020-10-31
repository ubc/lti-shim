<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformClient extends Model
{
    use HasFactory;

    protected $fillable = ['platform_id', 'tool_id', 'client_id'];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }
}
