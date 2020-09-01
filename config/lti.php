<?php

return [
    'iss' => config('app.url'),

    // shim's platform information is stored in the database, so we need the id
    'own_platform_id' => 1,
    // shim's tool information is stored in the database, so we need the id
    'own_tool_id' => 1,
];
