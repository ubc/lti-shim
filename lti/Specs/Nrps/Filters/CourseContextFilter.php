<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\CourseContext;
use App\Models\LtiSession;
use App\Models\Nrps;

use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

class CourseContextFilter implements FilterInterface
{
    public function filter(array $params, Nrps $nrps): array
    {
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
        $courseTitle = null;
        if (isset($params[Param::CONTEXT][Param::TITLE]))
            $courseTitle = $params[Param::CONTEXT][Param::TITLE];
        $courseLabel = null;
        if (isset($params[Param::CONTEXT][Param::LABEL]))
            $courseLabel = $params[Param::CONTEXT][Param::LABEL];
        // get the course mapping so we can give the fake id
        $courseContext = CourseContext::createOrGet(
            $nrps->deployment_id,
            $nrps->tool_id,
            $params[Param::CONTEXT]['id'],
            $courseTitle,
            $courseLabel
        );
        $newContext = ['id' => $courseContext->fake_context_id];
        // we can pass through the course title and label as is
        if ($courseTitle) $newContext[Param::TITLE] = $courseTitle;
        if ($courseLabel) $newContext[Param::LABEL] = $courseLabel;
        $params[Param::CONTEXT] = $newContext;
        return $params;
    }
}
