<?php
namespace UBC\LTI;

use \Exception;
use Illuminate\Support\Facades\Log;

class LtiException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Log::channel('lti')->error($message);
    }
}
