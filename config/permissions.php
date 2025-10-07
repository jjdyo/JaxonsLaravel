<?php

return [
    // Map permission names to the minimum role level required (see config/roles.php hierarchy)
    // Higher number = higher privilege. 0 or missing means disabled/not enforced by level.
    'levels' => [
        'view about page url' => 3,
        'view functions page' => 3,
    ],

    // Guard to use when working with permissions (Spatie Permission)
    'guard' => 'web',
];
