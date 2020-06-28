<?php

namespace App\Models;

use Faker\Factory as Faker;

use Illuminate\Database\Eloquent\Model;

class CourseContext extends Model
{
    protected $fillable = [
        'deployment_id',
        'real_context_id',
        'tool_id'
    ];

    // get the given CourseContext by tool_id, deployment_id and real_context_id
    // will create one if it doesn't already exist
    public static function createOrGet(
        int $deploymentId,
        int $toolId,
        string $realContextId
    ): CourseContext {
        $courseContext = CourseContext::firstOrCreate([
            'tool_id' => $toolId,
            'deployment_id' => $deploymentId,
            'real_context_id' => $realContextId
        ]);
        if (!$courseContext->fake_context_id)
            $courseContext->fillFakeFields();
        return $courseContext;
    }

    public function fillFakeFields()
    {
        $faker = Faker::create();
        $this->fake_context_id = $faker->sha256;
        $this->save();
    }
}
