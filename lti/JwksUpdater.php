<?php
namespace UBC\LTI;

use Illuminate\Support\Facades\Log;

use App\Models\AbstractLtiService;

use Jose\Component\Core\JWKSet;

use UBC\LTI\LTIException;
use UBC\LTI\Param;

class JwksUpdater
{
    // grab JWKS from the jwks_url and save them to the database
    public static function update(AbstractLtiService $service)
    {
        if (empty($service->jwks_url)) return; // no url to update with

        $jwks = file_get_contents($service->jwks_url);
        if ($jwks === false) 
            throw new LTIException('Failed to get data from JWKS URL.');

        try {
            $jwks = JWKSet::createFromJson($jwks);
            // save the keys into the database using mass assignment, this means
            // we won't have to specify PlatformKey or ToolKey models
            $keys = [];
            foreach ($jwks as $jwk) {
                $keys[] = [
                    Param::KID => $jwk->get(Param::KID),
                    'key' => json_encode($jwk)
                ];
            }
            $service->keys()->createMany($keys);
        }
        catch(\InvalidArgumentException $e) {
            throw new LTIException(
                "Failed to get keys from JWKS URL.", 0, $e);
        }
    }
}
