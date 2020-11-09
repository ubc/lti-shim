<?php
namespace UBC\LTI\Utils;

use Exception;

use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Log;

class LtiException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Log::channel('lti')->error($message);
    }

    /**
     * Avoid having to manually catch LtiException in the controllers by
     * telling Laravel to auto catch them.
     */
    public function render($request)
    {
        // not using default error rendering, since that results in a 500
        // error, we want to send back a 400 error
        abort(Response::HTTP_BAD_REQUEST, $this->getMessage());
    }
}
