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
        // check required fields exist
        if (!isset($params[Param::CONTEXT_URI])) return $params;
        if (!isset($params[Param::CONTEXT_URI]['id'])) {
            Log::error('Course context missing missing required id: ' .
                print_r($params, true));
            $params[Param::CONTEXT_URI] = [];
            return $params;
        }
        // replace real values with fake
        $courseContext = CourseContext::getByRealContext(
            $session->deployment_id,
            $session->tool_id,
            $params[Param::CONTEXT_URI]['id']
        );
        $newContext = ['id' => $courseContext->fake_context_id];
        $params[Param::CONTEXT_URI] = $newContext;
        return $params;
    }
}
