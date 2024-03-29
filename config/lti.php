<?php

return [
    'iss' => config('app.url'),
    // domain we will use to generate fake emails in lti_fake_users
    'fake_email_domain' => env('FAKE_EMAIL_DOMAIN', 'example.com'),
    // shim has a platform and tool side, both needs an entry in the database.
    // we can retrieve the shim platform using the iss, so we just need a similar
    // unique id to retrieve the shim tool side.
    'own_tool_client_id' => "Do not change, used to identify shim's tool side. Not used otherwise."
];
