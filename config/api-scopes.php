<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Token Scopes
    |--------------------------------------------------------------------------
    |
    | This file defines the available scopes for API tokens in the application.
    | Add or remove scopes as needed for your application's functionality.
    |
    */

    'scopes' => [
        'site-backups' => 'Perform site backups',
        'site-spinup' => 'Spin up new sites',
        'read-data' => 'Read application data',
        'write-data' => 'Write application data',
    ],
];
