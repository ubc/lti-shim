<?php

namespace UBC\LTI\Specs\Launch\Filters;

use App\Models\LtiSession;

use UBC\LTI\Filters\AbstractWhitelistFilter;
use UBC\LTI\Specs\Launch\Filters\FilterInterface;
use UBC\LTI\Utils\Param;

// Remove any parameters that we do not recognize. This does not check the
// parameter values at all, only looking at the parameter name.
class WhitelistFilter extends AbstractWhitelistFilter implements FilterInterface
{
    protected const LOG_HEADER = 'Whitelist Filter';

    // list of params that show up in oidc login
    // needs to be an associative array instead of regular array for faster
    // lookups, not sure if it matters though given the small arrays
    public const LOGIN_PARAMS = [
        Param::ISS => 1,
        Param::LOGIN_HINT => 2,
        Param::TARGET_LINK_URI => 3,
        Param::CLIENT_ID => 4,
        Param::LTI_MESSAGE_HINT => 5,
        Param::LTI_DEPLOYMENT_ID => 6
    ];
    // list of params that show up in auth requests
    public const AUTH_REQ_PARAMS = [
        Param::SCOPE => 1,
        Param::RESPONSE_TYPE => 2,
        Param::CLIENT_ID => 3,
        Param::REDIRECT_URI => 4,
        Param::LOGIN_HINT => 5,
        Param::STATE => 6,
        Param::RESPONSE_MODE => 7,
        Param::NONCE => 8,
        Param::PROMPT => 9,
        Param::LTI_MESSAGE_HINT => 10
    ];
    // list of params that show up in auth responses
    public const AUTH_RESP_PARAMS = [
        Param::STATE => 1,
        Param::ID_TOKEN => 2
    ];
    // list of claims that are required to show up in decoded id_token
    public const ID_TOKEN_REQUIRED_CLAIMS = [
        Param::TYP => 1,
        Param::ALG => 2,
        Param::KID => 3,
        Param::ISS => 4,
        Param::SUB => 5,
        Param::AUD => 6,
        Param::EXP => 7,
        Param::IAT => 8,
        Param::NONCE => 9,
        Param::AZP => 10,
        Param::NBF => 11,
        Param::MESSAGE_TYPE_URI => 12,
        Param::VERSION_URI => 13,
        Param::DEPLOYMENT_ID_URI => 14,
        Param::TARGET_LINK_URI_URI => 15,
        Param::RESOURCE_LINK_URI => 16,
        Param::ROLES_URI => 17
    ];

    // list of claims that could be in id_token
    public const ID_TOKEN_OPTIONAL_CLAIMS = [
        Param::NAME => 1,
        Param::EMAIL => 2,
        Param::LAUNCH_PRESENTATION_URI => 3,
        Param::CONTEXT_URI => 4,
        Param::NRPS_CLAIM_URI => 5,
        Param::AGS_CLAIM_URI => 6,
        Param::FOR_USER_URI => 7
    ];

    protected array $whitelists = [
        self::LOGIN_PARAMS,
        self::AUTH_REQ_PARAMS,
        self::AUTH_RESP_PARAMS,
        self::ID_TOKEN_REQUIRED_CLAIMS,
        self::ID_TOKEN_OPTIONAL_CLAIMS
    ];

    public function filter(array $params, LtiSession $session): array
    {
        $this->ltiLog->debug('Running');
        return $this->apply($params);
    }
}
