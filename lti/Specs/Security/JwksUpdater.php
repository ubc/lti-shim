<?php
namespace UBC\LTI\Specs\Security;

use Illuminate\Support\Facades\Log;

use App\Models\AbstractLtiEntity;

use Jose\Component\Core\JWKSet;

use UBC\LTI\LTIException;
use UBC\LTI\Param;

class JwksUpdater
{
    // grab JWKS from the jwks_url and save them to the database
    public static function update(AbstractLtiEntity $service)
    {
        if (empty($service->jwks_url)) return; // no url to update with

        $jwks = file_get_contents($service->jwks_url);
        if ($jwks === false)
            throw new LTIException('Failed to get data from JWKS URL.');

        try {
            $jwks = JWKSet::createFromJson($jwks);
            // save the keys into the database using mass assignment
            $keys = [];
            // to make sure the key has a proper foreign key, we need to know
            // if this service is a tool or platform, we can grab that from the
            // table name of the key model
            $tableName = strtok($service->keys()->getRelated()->getTable(), '_');
            foreach ($jwks as $jwk) {
                $keys[] = [
                    $tableName . '_id' => $service->id,
                    Param::KID => $jwk->get(Param::KID),
                    'key' => json_encode($jwk)
                ];
            }
            $service->keys()->insertOrIgnore($keys);
        }
        catch(\InvalidArgumentException $e) {
            throw new LTIException(
                "Failed to get keys from JWKS URL.", 0, $e);
        }
    }
}
