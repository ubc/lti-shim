<?php
namespace UBC\LTI\Specs;

use Illuminate\Support\Facades\Log;

use UBC\LTI\LTIException;

class ParamChecker
{
    private array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    // check that the required params are in the request and is not empty
    // throw LTIException if params missing
    public function requireParams(array $requiredParams)
    {
        foreach ($requiredParams as $requiredParam) {
            if (!$this->hasParam($requiredParam)) {
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
            if (!$this->hasParam($key)) {
                throw new LTIException(
                    "Missing required parameter '$key'");
            }
            if ($this->params[$key] != $val) {
                throw new LTIException(
                    "Required parameter '$key' must be set to '$val'");
            }
        }
    }

    private function hasParam(string $param): bool
    {
        if (array_key_exists($param, $this->params) &&
            !empty($this->params[$param])) {
            return true;
        }
        return false;
    }
}
