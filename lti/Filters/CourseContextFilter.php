<?php

namespace UBC\LTI\Filters;

use App\Models\CourseContext;
use App\Models\LtiSession;

use UBC\LTI\Filters\FilterInterface;
use UBC\LTI\Param;

class CourseContextFilter implements FilterInterface
{
    public function filter(array $params, LtiSession $session): array
    {
        if (isset($params[Param::CONTEXT_URI])) {
            $courseContext = CourseContext::firstOrCreate([
                'deployment_id' => $session->deployment_id,
                'real_context_id' => $params[Param::CONTEXT_URI]['id']
            ]);
            if (!$courseContext->fake_context_id)
                $courseContext->fillFakeFields();

            $newContext = ['id' => $courseContext->fake_context_id];
            $params[Param::CONTEXT_URI] = $newContext;
        }
        return $params;
    }
}
