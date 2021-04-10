<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

use App\Models\AbstractLtiEntity;

class Tool extends AbstractLtiEntity
{
    use HasFactory;

    public const TARGET_TOOL_PARAM = 'target_tool_id';

    protected $fillable = ['name', 'client_id', 'oidc_login_url',
        'auth_resp_url', 'target_link_uri', 'jwks_url'];
    protected $with = ['keys']; // eager load keys
    // make sure shim_target_link_uri ends up in the JSON representation
    protected $appends = ['shim_target_link_uri'];

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

    public function getShimTargetLinkUriAttribute($value)
    {
        $shimTool = Tool::getOwnTool();
        $uri = Uri::createFromString($shimTool->target_link_uri);
        return UriModifier::appendQuery($uri, self::TARGET_TOOL_PARAM . '=' .
                                              $this->id);
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

    /**
     * Each Tool should have its own unique target_link_uri, that's how we can
     * distinguish which tool is being accessed on launch to the shim.  This is
     * generated by getShimTargetLinkUriAttribute(), we're just 'reversing' in
     * here to get tool back.
     *
     * TODO: could be refactored out if Deep Link launch flow works out.
     */
    public static function getByTargetLinkUri(string $targetLinkUri): ?self
    {
        // fail if the target link uri doesn't even point to the shim
        if (strpos($targetLinkUri, config('app.url')) !== 0) return null;

        $query = Query::createFromUri(Uri::createFromString($targetLinkUri));
        $toolId = $query->get(self::TARGET_TOOL_PARAM);
        // fail if the tool id isn't even a number
        if (!is_numeric($toolId)) return null;

        $toolId = intval($toolId);
        return self::find($toolId);
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
