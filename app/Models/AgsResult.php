<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AgsResult extends Model
{
//    use HasFactory;

    protected $with = ['ags_lineitems'];

    public function ags_lineitems()
    {
        return $this->belongsTo('App\Models\AgsLineitem');
    }

    public function getShimUrlAttribute()
    {
        return route('lti.ags.result',
            [
                'ags' => $this->ags_lineitems->ags_id,
                'lineitem' => $this->ags_lineitems_id,
                'result' => $this->id
            ]
        );
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
                'ags_lineitems_id' => $lineitemId
            ];
        }

        $res = self::insertOrIgnore($resultsInfo);
        // since insertOrIgnore doesn't return the created rows,
        // we have to do a separate query for them
        $results = self::where('ags_lineitems_id', $lineitemId)
                         ->whereIn('result', $resultUrls)
                         ->get();

        return $results;
    }

}
