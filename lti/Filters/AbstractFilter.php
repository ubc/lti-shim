<?php
namespace UBC\LTI\Filters;

use UBC\LTI\LtiLog;

class AbstractFilter
{
    protected const LOG_HEADER = 'Abstract Filter';

    protected LtiLog $ltiLog;

    public function __construct()
    {
        $this->ltiLog = new LtiLog(static::LOG_HEADER);
    }
}
