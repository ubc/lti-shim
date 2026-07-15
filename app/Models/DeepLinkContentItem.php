<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DeepLinkContentItem extends Model
{
    use HasFactory;

    public function deployment()
    {
        return $this->belongsTo('App\Models\Deployment');
    }

    public function tool()
    {
        return $this->belongsTo('App\Models\Tool');
    }

    public function getShimLaunchUrlAttribute()
    {
        return route('lti.launch.dl.contentItemLaunch',
            ['toolId' => $this->tool_id, 'deepLinkContentItemId' => $this->id]);
    }

    public static function createOrGet(
        string $url,
        int $deploymentId,
        int $toolId
    ): self {
        $contentItem = self::where([
            'url' => $url,
            'deployment_id' => $deploymentId,
            'tool_id' => $toolId
        ])->first();
        if (!$contentItem) {
            // no existing entry, create a new one
            $contentItem = new self;
            $contentItem->url = $url;
            $contentItem->deployment_id = $deploymentId;
            $contentItem->tool_id = $toolId;
            $contentItem->save();
        }
        return $contentItem;
    }

}
