<?php

return [
    // Define role hierarchy using integer levels. Higher number = higher privilege.
    // Update this map to insert new roles (e.g., 'csc' => 2 between user and moderator).
    'hierarchy' => [
        'user' => 1,
        'csc' => 2,
        'moderator' => 3,
        'admin' => 4,
    ],

    // Guard to use when working with roles (Spatie Permission)
    'guard' => 'web',
];
