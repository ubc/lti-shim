<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Models\AbstractLtiEntity;

class Platform extends AbstractLtiEntity
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iss',
        'auth_req_url',
        'access_token_url',
        'jwks_url'
    ];
    protected $with = ['keys']; // eage load clients and keys

    public function clients()
    {
        return $this->hasMany('App\Models\PlatformClient');
    }

    public function deployments()
    {
        return $this->hasMany('App\Models\Deployment');
    }

    public function keys()
    {
        return $this->hasMany('App\Models\PlatformKey');
    }

    public function getPlatformClient(string $clientId)
    {
        return $this->clients()->firstWhere('client_id', $clientId);
    }

    public function updateWithRelations($info)
    {
        $this->update($info);
        // we're cheating a bit here, as the ui doesn't implement editing keys,
        // and deletes are handled by another call, so we only have to worry
        // about adding in new keys
        $new = [];
        foreach ($info['keys'] as $key) {
            if (!isset($key['id'])) array_push($new, $key);
        }
        $this->keys()->createMany($new);
    }

    public static function getAllEditable(): Collection
    {
        // we don't want users to be able to edit the shim's own platform
        // configuration, so exclude it
        return self::where('id', '!=', config('lti.own_platform_id'))->get();
    }

    public static function getByIss(string $iss): ?self
    {
        return self::firstWhere('iss', $iss);
    }

    // get the shim's platform entry
    public static function getOwnPlatform(): self
    {
        $platform = self::find(config('lti.own_platform_id'));
        if (!$platform) {
            throw new \UnexpectedValueException(
                "Missing own platform information, did you seed the database?");
        }
        return $platform;
    }
}
