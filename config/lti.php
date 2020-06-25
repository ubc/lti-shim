<?php

return [
    'iss' => config('app.url'),
    
    // shim's platform information is stored in the database, so we need the id
    'own_platform_id' => 1,
    // shim's tool information is stored in the database, so we need the id
    'own_tool_id' => 1,
    // shim's own lti paths, we use these to seed the database and configure
    // the router
    'tool_jwks_path' => '/lti/tool/jwks',
    'tool_launch_login_path' => '/lti/launch/tool/login',
    'tool_launch_auth_resp_path' => '/lti/launch/tool/auth',
    'platform_jwks_path' => '/lti/platform/jwks',
    'platform_launch_login_path' => '/lti/launch/platform/login',
    'platform_launch_auth_req_path' => '/lti/launch/platform/auth',
    'platform_security_token_path' => '/lti/security/platform/token',
    'platform_nrps_path' => '/lti/platform/nrps/{nrps}'
];
