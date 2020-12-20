<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Models\AddToUrlTrait;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class Nrps extends Model
{
    use AddToUrlTrait;
    use HasFactory;

    public function course_context()
    {
        return $this->belongsTo('App\Models\CourseContext');
    }

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
        return route('lti.nrps', ['nrps' => $this->id]);
    }

    public function getContextMembershipsUrl(array $params = []): string
    {
        return $this->addToUrl($this->context_memberships_url, $params);
    }

    public function getShimUrl(array $params = []): string
    {
        return $this->addToUrl($this->shim_url, $params);
    }

    public static function getByUrl(
        string $url,
        int $courseContextId,
        int $deploymentId,
        int $toolId
    ): ?self {
        $nrps = self::where([
            'context_memberships_url' => $url,
            'course_context_id' => $courseContextId,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId
        ])->first();
        return $nrps;
    }

    public static function createOrGet(
        string $url,
        int $courseContextId,
        int $deploymentId,
        int $toolId
    ): self {
        $nrps = self::getByUrl($url, $courseContextId, $deploymentId, $toolId);
        if (!$nrps) {
            // no existing entry, create a new one
            $nrps = new self;
            $nrps->context_memberships_url = $url;
            $nrps->course_context_id = $courseContextId;
            $nrps->deployment_id = $deploymentId;
            $nrps->tool_id = $toolId;
            $nrps->save();
        }
        return $nrps;
    }
}
