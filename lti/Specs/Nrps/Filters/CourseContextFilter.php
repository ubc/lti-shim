<?php

namespace UBC\LTI\Specs\Nrps\Filters;

use Illuminate\Support\Facades\Log;

use App\Models\CourseContext;
use App\Models\LtiSession;
use App\Models\Nrps;

use UBC\LTI\Filters\AbstractFilter;
use UBC\LTI\Specs\Nrps\Filters\FilterInterface;
use UBC\LTI\Param;

class CourseContextFilter extends AbstractFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Course Context Filter';

    public function filter(array $params, Nrps $nrps): array
    {
        // check required fields exist
        if (!isset($params[Param::CONTEXT])) {
            $this->ltiLog->debug('Skipping', $nrps);
            return $params;
        }
        if (!isset($params[Param::CONTEXT]['id'])) {
            // the id is required by spec, if it doesn't exist, then we have
            // a problem. For now, just log the error and drop the section.
            $this->ltiLog->error('NRPS response missing context id: ' .
                json_encode($params), $nrps);
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
        $this->ltiLog->debug('Rewrite course context', $nrps, $courseContext);
        $newContext = ['id' => $courseContext->fake_context_id];
        // we can pass through the course title and label as is
        if ($courseTitle) $newContext[Param::TITLE] = $courseTitle;
        if ($courseLabel) $newContext[Param::LABEL] = $courseLabel;
        $params[Param::CONTEXT] = $newContext;
        return $params;
    }
}
