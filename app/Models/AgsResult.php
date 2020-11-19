<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AgsResult extends Model
{
    use HasFactory;

    protected $with = ['ags_lineitem'];

    public function ags_lineitem()
    {
        return $this->belongsTo('App\Models\AgsLineitem');
    }

    public function getShimUrlAttribute()
    {
        return route('lti.ags.result',
            [
                'ags' => $this->ags_lineitem->ags_id,
                'lineitem' => $this->ags_lineitem_id,
                'result' => $this->id
            ]
        );
    }

    public static function createOrGet(string $resultUrl, int $lineitemId): self
    {
        $result = self::where('result', $resultUrl)
                        ->where('ags_lineitem_id', $lineitemId)
                        ->first();
        if (!$result) {
            // no existing entry, create a new one
            $result = new self;
            $result->result = $resultUrl;
            $result->ags_lineitem_id = $lineitemId;
            $result->save();
        }
        return $result;
    }

    public static function createOrGetAll(
        array $resultUrls,
        int $lineitemId
    ): Collection {
        $results = [];

        $resultsInfo = [];
        foreach ($resultUrls as $resultUrl) {
            $resultsInfo[] = [
                'result' => $resultUrl,
                'ags_lineitem_id' => $lineitemId
            ];
        }

        $res = self::insertOrIgnore($resultsInfo);
        // since insertOrIgnore doesn't return the created rows,
        // we have to do a separate query for them
        $results = self::where('ags_lineitem_id', $lineitemId)
                         ->whereIn('result', $resultUrls)
                         ->get();

        return $results;
    }

}
