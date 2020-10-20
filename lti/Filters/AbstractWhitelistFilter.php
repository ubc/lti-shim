<?php
namespace UBC\LTI\Filters;

use Illuminate\Support\Facades\Log;

use UBC\LTI\Filters\AbstractFilter;

// general whitelist implementation since each spec needs its own whitelist
abstract class AbstractWhitelistFilter extends AbstractFilter
{
    // Array of arrays, so we can have multiple whitelists. To allow fast
    // lookup, each whitelist needs to be an associative array, they should
    // look something like:
    // [ 'AllowedKey1' => 1, 'AllowedKey2' => 2, 'AllowedKey3' => 3 ]
    // The array key contains the actual allowed word. The value of '1', '2',
    // etc isn't used, I just put an incrementing counter for convenience.
    protected array $whitelists = [];

    protected function apply(array $params): array
    {
        foreach ($params as $key => $val) {
            $allowed = false;
            foreach ($this->whitelists as $whitelist) {
                if (isset($whitelist[$key])) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $this->ltiLog->debug('Removed Key: ' . $key);
                unset($params[$key]);
            }
        }
        return $params;
    }
}
