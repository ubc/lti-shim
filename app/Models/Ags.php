<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class Ags extends Model
{
    use HasFactory;

    // need to tell Laravel to auto decode our JSON column
    protected $casts = [
        'scopes' => 'array'
    ];

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

    public function getShimLineitemsUrlAttribute()
    {
        return route('lti.ags.lineitems', ['ags' => $this->id]);
    }

    public function getShimLineitemsUrl(array $params = []): string
    {
        return $this->addParamsToUrl($this->shim_lineitems_url, $params);
    }

    // The urls might have existing GET params. If we want to add
    // additional params, then we need to rebuild the URL.
    private function addParamsToUrl(string $url, array $params): string
    {
        // not adding any params, return as is
        if (!$params) return $url;

        $uri = Uri::createFromString($url);
        $query = Query::createFromParams($params);
        $uri = UriModifier::mergeQuery($uri, $query);

        return $uri;
    }

    public static function getByLineitems(
        string $lineitemsUrl,
        int $courseContextId,
        int $deploymentId,
        int $toolId
    ): ?self {
        $fields = [
            'lineitems' => $lineitemsUrl,
            'course_context_id' => $courseContextId,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId
        ];
        $ags = self::where($fields)->first();
        return $ags;
    }

    public static function createOrGet(
        string $lineitemsUrl,
        int $courseContextId,
        int $deploymentId,
        int $toolId,
        array $scopes = []
    ): self {
        $ags = self::getByLineitems($lineitemsUrl, $courseContextId,
            $deploymentId, $toolId);
        if (!$ags) {
            // no existing entry, create a new one
            $ags = new self;
            $ags->lineitems = $lineitemsUrl;
            $ags->course_context_id = $courseContextId;
            $ags->deployment_id = $deploymentId;
            $ags->tool_id = $toolId;
            if ($scopes) $ags->scopes = $scopes;
            $ags->save();
        }
        return $ags;
    }
}
