<?php

namespace UBC\LTI\Specs\Launch\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\CourseContext;
use App\Models\LtiSession;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Utils\LtiLog;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

class CourseContextFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Course Context Filter';

    public function filter(array $params, LtiSession $session): array
    {
        // don't do anything if field doesn't even exist
        if (!isset($params[Param::CONTEXT_URI])) {
            $this->ltiLog->debug('Skipping', $session);
            return $params;
        }
        $this->ltiLog->debug('Running', $session);

        $courseId = self::getContextId($params);
        if (!$courseId) {
            $params[Param::CONTEXT_URI] = [];
            return $params;
        }
        $courseTitle = null;
        if (isset($params[Param::CONTEXT_URI][Param::TITLE]))
            $courseTitle = $params[Param::CONTEXT_URI][Param::TITLE];
        $courseLabel = null;
        if (isset($params[Param::CONTEXT_URI][Param::LABEL]))
            $courseLabel = $params[Param::CONTEXT_URI][Param::LABEL];
        // replace real values with fake
        $courseContext = CourseContext::createOrGet(
            $session->deployment_id,
            $session->tool_id,
            $courseId,
            $courseTitle,
            $courseLabel
        );
        $this->ltiLog->debug('Course Context: ' . $courseContext->id, $session);
        $newContext = ['id' => $courseContext->fake_context_id];
        // we can pass through the course title and label as is
        if ($courseLabel) $newContext[Param::TITLE] = $courseTitle;
        if ($courseTitle) $newContext[Param::LABEL] = $courseLabel;

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
