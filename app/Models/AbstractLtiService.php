<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

use Jose\Component\Core\JWK;

use App\Models\AbstractRsaKey;

use UBC\LTI\JwksUpdater;
use UBC\LTI\LTIException;
use UBC\LTI\Param;

// parent class for Tool and Platform models
abstract class AbstractLtiService extends Model
{
    public function setJwksUrlAttribute($url)
    {
        // In laravel, validation is usually done in controller, but since we
        // might call the model alone, this is just in case.
        // Can't use filter_var() validation because it doesn't support utf-8
        if (parse_url($url, PHP_URL_SCHEME) && parse_url($url, PHP_URL_HOST))
            $this->attributes['jwks_url'] = $url;
        throw new LTIException("JWKS URL not recognized as a valid URL.");
    }

    // retrieve the RSA key used for signatures on this platform/tool
    public function getKey(string $kid = ''): AbstractRsaKey
    {
        try {
            // not retrieving by kid, so just return newest key
            if (empty($kid)) return $this->keys()->latest('id')->firstOrFail();
            // find by kid
            $key = $this->keys()->firstWhere(Param::KID, $kid);
            if ($key) return $key;
            // no existing key found, so update list of keys
            JwksUpdater::update($this);
            // if we still don't have the key after update, error out
            return $this->keys()->where(Param::KID, $kid)->firstOrFail();
        }
        catch (ModelNotFoundException $e) {
            $serviceType = Str::singular($this->getTable()); // tool or platform
            $msg = "Unable to find an RSA key for this $serviceType.";
            if ($kid) $msg .= " The key ID was '$kid'.";
            throw new \UnexpectedValueException($msg);
        }
    }
}
