<?php

return [
    'iss' => config('app.url'),
    'oidc_login_url' => config('app.url') . '/lti/launch/tool/login',
    'auth_req_url' => config('app.url') . '/lti/launch/platform/auth',
    'auth_resp_url' => config('app.url') . '/lti/launch/tool/auth' 
];
