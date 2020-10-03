<?php
namespace UBC\LTI\Filters;

use UBC\LTI\Utils\LtiLog;

class AbstractFilter
{
    protected const LOG_HEADER = 'Abstract Filter';

    protected LtiLog $ltiLog;

    public function __construct(LtiLog $ltiLog)
    {
        $this->ltiLog = new LtiLog(static::LOG_HEADER, $ltiLog->getStreamId());
    }
}
