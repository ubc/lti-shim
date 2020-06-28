<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\CourseContext;
use App\Models\LtiSession;

use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

class CourseContextFilter implements FilterInterface
{
    public function filter(
        array $params,
        int $deploymentId,
        int $toolId
    ): array {
        // check required fields exist
        if (!isset($params[Param::CONTEXT])) return $params;
        if (!isset($params[Param::CONTEXT]['id'])) {
            // the id is required by spec, if it doesn't exist, then we have
            // a problem. For now, just log the error and drop the section.
            Log::error('NRPS response missing context id: ' .
                print_r($params, true));
            $params[Param::CONTEXT] = [];
            return $params;
        }
        // get the course mapping so we can give the fake id
        $courseContext = CourseContext::createOrGet(
            $deploymentId,
            $toolId,
            $params[Param::CONTEXT]['id']
        );
        $newContext = ['id' => $courseContext->fake_context_id];
        $params[Param::CONTEXT] = $newContext;
        return $params;
    }
}
