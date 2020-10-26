<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class AgsLineitem extends Model
{
    public function ags()
    {
        return $this->belongsTo('App\Models\Ags');
    }

    public function getShimLineitemUrlAttribute()
    {
        return route('lti.ags.lineitem',
            ['ags' => $this->ags_id, 'lineitem' => $this->id]);
    }

    public function getShimLineitemUrl(array $params = []): string
    {
        return $this->addParamsToUrl($this->shim_lineitem_url, $params);
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

    public static function getByLineitem(
        string $lineitemUrl,
        int $agsId
    ): ?self {
        $fields = [
            'lineitem' => $lineitemUrl,
            'ags_id' => $agsId
        ];
        $agsLineitem = self::where($fields)->first();
        return $agsLineitem;
    }

    public static function createOrGet(
        string $lineitemUrl,
        int $agsId
    ): self {
        $agsLineitem = self::getByLineitem($lineitemUrl, $agsId);
        if (!$agsLineitem) {
            // no existing entry, create a new one
            $agsLineitem = new self;
            $agsLineitem->lineitem = $lineitemUrl;
            $agsLineitem->ags_id = $agsId;
            $agsLineitem->save();
        }
        return $agsLineitem;
    }
}
