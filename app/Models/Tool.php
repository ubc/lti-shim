<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    public function deployments()
    {
        return $this->hasMany('App\Models\Deployment');
    }

    public function keys()
    {
        return $this->hasMany('App\Models\ToolKey');
    }

    public static function getOwnTool(): Tool
    {
        $tool = self::find(config('lti.own_tool_id'));
        if (!$tool) {
            throw new \UnexpectedValueException(
                "Missing own tool information, did you seed the database?");
        }
        return $tool;
    }
}
