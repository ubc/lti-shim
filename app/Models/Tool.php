<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use App\Models\AbstractLtiService;

class Tool extends AbstractLtiService
{
    protected $fillable = ['name', 'client_id', 'oidc_login_url',
        'auth_resp_url', 'target_link_uri', 'jwks_url'];
    protected $with = ['keys']; // eager load keys

    public function keys()
    {
        return $this->hasMany('App\Models\ToolKey');
    }

    public function clients()
    {
        return $this->hasMany('App\Models\PlatformClient');
    }

    public function getPlatformClient(int $platformId)
    {
        return $this->clients()->firstWhere('platform_id', $platformId);
    }

    public function updateWithRelations($info)
    {
        $this->update($info);
        // we're cheating a bit here, as the ui doesn't implement editing
        // keys, and deletes are handled by another call, so we
        // only have to worry about adding in new keys
        $new = [];
        foreach ($info['keys'] as $key) {
            if (!isset($key['id'])) array_push($new, $key);
        }
        $this->keys()->createMany($new);
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

    public static function getAllEditable(): Collection
    {
        return self::where('id', '!=', config('lti.own_tool_id'))->get();
    }
}
