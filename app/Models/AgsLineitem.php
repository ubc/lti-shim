<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;

use UBC\LTI\Utils\Param;

// this model basically maps the original Names and Role Provisioning Service
// request to the one that the shim provides
class AgsLineitem extends Model
{
    use HasFactory;

    protected $fillable = ['lineitem', 'ags_id'];

    public function ags()
    {
        return $this->belongsTo('App\Models\Ags');

    }

    public function ags_results()
    {
        return $this->hasMany('App\Models\AgsResult');
    }

    /**
     * Add a /results to the original lineitem url path
     */
    public function getLineitemResultsAttribute()
    {
        // the result url might have queries and such, but we want to append
        // to the path, this means using the Uri package to modify the url
        return $this->addToUrl($this->lineitem, [], Param::AGS_RESULTS_PATH);
    }

    /**
     * Add a /scores to the original lineitem url path
     */
    public function getLineitemScoresAttribute()
    {
        return $this->addToUrl($this->lineitem, [], Param::AGS_SCORES_PATH);
    }

    /**
     * Build a url to this lineitem on the shim.
     */
    public function getShimLineitemUrlAttribute()
    {
        return route('lti.ags.lineitem',
            ['ags' => $this->ags_id, 'lineitem' => $this->id]);
    }

    /**
     * Build a url to this lineitem's results on the shim.
     */
    public function getShimLineitemResultsUrlAttribute()
    {
        return route('lti.ags.results',
            ['ags' => $this->ags_id, 'lineitem' => $this->id]);
    }

    /**
     * Build a url to this lineitem's scores on the shim.
     */
    public function getShimLineitemScoresUrlAttribute()
    {
        return route('lti.ags.scores',
            ['ags' => $this->ags_id, 'lineitem' => $this->id]);
    }

    /**
     * Add queries to the original lineitem results url
     */
    public function getLineitemResultsUrl(array $params = []): string
    {
        return $this->addToUrl($this->lineitem_results, $params);
    }

    /**
     * Add queries to the lineitem results url on the shim
     */
    public function getShimLineitemUrl(array $params = []): string
    {
        return $this->addToUrl($this->shim_lineitem_url, $params);
    }

    /**
     * The urls might have existing GET queries. If we want to add additional
     * queries or if we want to append to the path, then we need to rebuild the
     * URL.
     */
    private function addToUrl(
        string $url,
        array $queries = [],
        string $path = ''
    ): string {
        $uri = Uri::createFromString($url);
        if ($path) $uri = UriModifier::appendSegment($uri, $path);
        if ($queries) {
            $query = Query::createFromParams($queries);
            $uri = UriModifier::mergeQuery($uri, $query);
        }

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

    public static function createOrGetAll(
        array $lineitemUrls,
        int $agsId
    ): Collection {
        $lineitems = [];

        $lineitemsInfo = [];
        foreach ($lineitemUrls as $lineitemUrl) {
            $lineitemsInfo[] = [
                'lineitem' => $lineitemUrl,
                'ags_id' => $agsId
            ];
        }

        $res = self::insertOrIgnore($lineitemsInfo);
        // since insertOrIgnore doesn't return the created rows,
        // we have to do a separate query for them
        $lineitems = self::where('ags_id', $agsId)
                         ->whereIn('lineitem', $lineitemUrls)
                         ->get();

        return $lineitems;
    }
}
