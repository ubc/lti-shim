<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class Nrps extends Model
{
    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }
    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }

    public function getShimUrlAttribute()
    {
        return route('nrps', ['nrps' => $this->id]);
    }

    public static function getByUrl(
        string $url,
        int $deploymentId,
        int $toolId
    ): self {
        $nrps = self::where([
            'context_memberships_url' => $url,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId
        ])->first();
        return $nrps;
    }

    public static function createOrGet(
        string $url,
        int $deploymentId,
        int $toolId
    ): self {
        $nrps = self::getByUrl($url, $deploymentId, $toolId);
        if (!$nrps) {
            // no existing entry, create a new one
            $nrps = new self;
            $nrps->context_memberships_url = $url;
            $nrps->deployment_id = $deploymentId;
            $nrps->tool_id = $toolId;
            $nrps->save();
        }
        return $nrps;
    }
}
