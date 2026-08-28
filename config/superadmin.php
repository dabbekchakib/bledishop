<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Development super admin account seeded by the SuperAdminSeeder.
    | Credentials must always come from the environment, never hard-coded.
    |
    */

    'name' => env('SUPERADMIN_NAME', 'Super Admin'),

    'email' => env('SUPERADMIN_EMAIL', 'admin@bledishop.test'),

    'password' => env('SUPERADMIN_PASSWORD', 'password'),
];
