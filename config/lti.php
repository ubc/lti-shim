<?php

return [
    'iss' => config('app.url'),
    'oidc_login_url' => config('app.url') . '/lti/launch/tool/login',
    'auth_req_url' => config('app.url') . '/lti/launch/platform/auth',
    'auth_resp_url' => config('app.url') . '/lti/launch/tool/auth',
    
    // shim's platform information is stored in the database, so we need the id
    'own_platform_id' => 1,
    // shim's tool information is stored in the database, so we need the id
    'own_tool_id' => 1
];
