<?php
namespace UBC\LTI\Specs;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use UBC\LTI\LTIException;

class RequestChecker
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // check that the required params are in the request, if a param
    // is missing, throw LTIException
    public function requireParams(array $requiredParams)
    {
        foreach ($requiredParams as $requiredParam) {
            if (!$this->request->filled($requiredParam)) {
                throw new LTIException(
                    "Missing required parameter '$requiredParam'");
            }
        }
    }

    // more strict than requireParams, not only do the params have to be
    // present, they have to match the value given, otherwise, throw
    // LTIException
    public function requireValues(array $requiredValues)
    {
        foreach ($requiredValues as $key => $val) {
            if (!$this->request->filled($key)) {
                throw new LTIException(
                    "Missing required parameter '$key'");
            }
            if ($this->request->input($key) != $val) {
                throw new LTIException(
                    "Required parameter '$key' must be set to '$val'");
            }
        }
    }
}
