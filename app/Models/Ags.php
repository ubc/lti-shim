<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Models\AddToUrlTrait;

use UBC\LTI\Utils\Param;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class Ags extends Model
{
    use AddToUrlTrait;
    use HasFactory;

    // need to tell Laravel to auto decode our JSON column
    protected $casts = [
        'scopes' => 'array'
    ];

    public function ags_lineitems()
    {
        return $this->hasMany('App\Models\AgsLineitem');
    }

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

    public function canReadOnlyLineitem(): bool
    {
        return in_array(Param::AGS_SCOPE_LINEITEM_READONLY_URI, $this->scopes);
    }

    public function canReadOnlyResult(): bool
    {
        return in_array(Param::AGS_SCOPE_RESULT_READONLY_URI, $this->scopes);
    }

    public function canWriteLineitem(): bool
    {
        return in_array(Param::AGS_SCOPE_LINEITEM_URI, $this->scopes);
    }

    public function canWriteScore(): bool
    {
        return in_array(Param::AGS_SCOPE_SCORE_URI, $this->scopes);
    }

    public function getLineitemScopes(bool $isReadOnly): array
    {
        $canWriteLineitem = $this->canWriteLineitem();
        $scopes = [];
        if ($isReadOnly) {
            if ($this->canReadOnlyLineitem())
                $scopes[] = Param::AGS_SCOPE_LINEITEM_READONLY_URI;
            if ($canWriteLineitem)
                $scopes[] = Param::AGS_SCOPE_LINEITEM_URI;
        }
        else {
            if ($canWriteLineitem)
                $scopes[] = Param::AGS_SCOPE_LINEITEM_URI;
        }
        return $scopes;
    }

    public function getLineitemsUrl(array $params = []): string
    {
        return $this->addToUrl($this->lineitems, $params);
    }

    public function getShimLineitemsUrlAttribute()
    {
        return route('lti.ags.lineitems', ['ags' => $this->id]);
    }

    public function getShimLineitemsUrl(array $params = []): string
    {
        return $this->addToUrl($this->shim_lineitems_url, $params);
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
        }
        // we need to update scopes, in case it changes on the platform
        if ($scopes) $ags->scopes = $scopes;
        $ags->save();
        return $ags;
    }
}
