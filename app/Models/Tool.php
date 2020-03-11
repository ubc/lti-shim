<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\AbstractLtiService;

class Tool extends AbstractLtiService
{
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
