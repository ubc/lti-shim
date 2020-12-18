<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Ramsey\Uuid\Uuid;

class CourseContext extends Model
{
    use HasFactory;

    protected $fillable = [
        'deployment_id',
        'real_context_id',
        'tool_id'
    ];

    // get the given CourseContext by tool_id, deployment_id and real_context_id
    // will create one if it doesn't already exist, will update title and label
    // if they've changed
    public static function createOrGet(
        int $deploymentId,
        int $toolId,
        string $realContextId,
        ?string $title = null,
        ?string $label = null
    ): self {
        $courseContext = self::firstOrCreate([
            'tool_id' => $toolId,
            'deployment_id' => $deploymentId,
            'real_context_id' => $realContextId
        ]);
        if (!$courseContext->fake_context_id)
            $courseContext->fillFakeFields();
        if ($title && $courseContext->title != $title)
            $courseContext->title = $title;
        if ($label && $courseContext->label != $label)
            $courseContext->label = $label;
        if ($courseContext->isDirty())
            $courseContext->save();
        return $courseContext;
    }

    public function fillFakeFields()
    {
        $this->fake_context_id = Uuid::uuid4()->toString();
    }
}
