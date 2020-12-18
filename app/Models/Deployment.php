<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Ramsey\Uuid\Uuid;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = ['lti_deployment_id', 'platform_id', 'tool_id'];
    protected $with = ['platform'];

    public function platform()
    {
        return $this->belongsTo('App\Models\Platform');
    }

    // auto-populate fake_lti_deployment_id if it's not filled
    public function fillFakeFields()
    {
        $this->fake_lti_deployment_id = Uuid::uuid4()->toString();
        $this->save();
    }
}
