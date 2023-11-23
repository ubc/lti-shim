<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

use App\Models\AbstractLtiEntity;

class Tool extends AbstractLtiEntity
{
    use HasFactory;

    /**
     * Route parameter for the OIDC login url that tells us what tool we're
     * targeting the launch to.
     */
    public const TARGET_TOOL_PARAM = 'toolId';

    protected $fillable = ['name', 'client_id', 'oidc_login_url',
        'auth_resp_url', 'target_link_uri', 'jwks_url', 'enable_midway_lookup'];
    protected $with = ['keys']; // eager load keys
    // make sure shim_target_link_uri ends up in the JSON representation
    protected $appends = ['shim_login_url', 'shim_target_link_uri'];

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

    public function getShimLoginUrlAttribute()
    {
        return route('lti.launch.login',
                     [self::TARGET_TOOL_PARAM => $this->id]);
    }

    /**
     * Unlike login, this doesn't change depending on the tool, but going to
     * keep using it in case we do need to do per-tool target_link_uri.
     */
    public function getShimTargetLinkUriAttribute()
    {
        return route('lti.launch.midway');
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

    public static function getOwnTool(): self
    {
        $tool = self::where('client_id', config('lti.own_tool_client_id'))
                      ->first();
        if (!$tool) {
            throw new \UnexpectedValueException(
                "Missing own tool information, did you seed the database?");
        }
        return $tool;
    }

    public static function getAllEditable(): Collection
    {
        return self::where('client_id', '!=', config('lti.own_tool_client_id'))
                     ->get();
    }
}
