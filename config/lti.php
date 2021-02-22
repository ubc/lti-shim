<?php

return [
    'iss' => config('app.url'),

    // shim has a platform and tool side, both needs an entry in the database.
    // we can retrieve the shim platform using the iss, so we just need a similar
    // unique id to retrieve the shim tool side.
    'own_tool_client_id' => "Do not change, used to identify shim's tool side. Not used otherwise."
];
