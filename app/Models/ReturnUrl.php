<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\AddToUrlTrait;

class ReturnUrl extends Model
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

    public function getUrl(array $queries = []): string
    {
        return $this->addToUrl($this->url, $queries);
    }

    public function getShimUrlAttribute()
    {
        return route('lti.core.return',
                     ['returnUrl' => $this->id, 'token' => $this->token]);
    }

    public function getShimUrl(array $queries = []): string
    {
        return $this->addToUrl($this->shim_url, $queries);
    }

    public static function createOrGet(
        string $url,
        int $courseContextId,
        int $deploymentId,
        int $toolId
    ): self {
        $fields = [
            'url' => $url,
            'course_context_id' => $courseContextId,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId
        ];
        $returnUrl = self::where($fields)->first();
        if (!$returnUrl) {
            $returnUrl = new self;
            $returnUrl->url = $url;
            $returnUrl->course_context_id = $courseContextId;
            $returnUrl->deployment_id = $deploymentId;
            $returnUrl->tool_id = $toolId;
            $returnUrl->token = bin2hex(random_bytes(6));
            $returnUrl->save();
        }
        return $returnUrl;
    }
}
