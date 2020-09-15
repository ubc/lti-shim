<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\CourseContext;
use App\Models\LtiSession;

use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Param;

class CourseContextFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        // don't do anything if field doesn't even exist
        if (!isset($params[Param::CONTEXT_URI])) return $params;

        $courseId = self::getContextId($params);
        if (!$courseId) {
            $params[Param::CONTEXT_URI] = [];
            return $params;
        }
        // replace real values with fake
        $courseContext = CourseContext::createOrGet(
            $session->deployment_id,
            $session->tool_id,
            $courseId
        );
        $newContext = ['id' => $courseContext->fake_context_id];
        // we can pass through the course label and title as is
        if (isset($params[Param::CONTEXT_URI][Param::LABEL])) {
            $newContext[Param::LABEL] =
                $params[Param::CONTEXT_URI][Param::LABEL];
        }
        if (isset($params[Param::CONTEXT_URI][Param::TITLE])) {
            $newContext[Param::TITLE] =
                $params[Param::CONTEXT_URI][Param::TITLE];
        }
        $params[Param::CONTEXT_URI] = $newContext;
        return $params;
    }

    public static function getContextId(array $params): string
    {
        // check required fields exist
        if (!isset($params[Param::CONTEXT_URI])) return "";
        if (!isset($params[Param::CONTEXT_URI]['id'])) {
            Log::error('Course context missing missing required id: ' .
                print_r($params, true));
            return "";
        }
        return $params[Param::CONTEXT_URI]['id'];
    }
}
