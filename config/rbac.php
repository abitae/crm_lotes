<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super-admin bootstrap (emails)
    |--------------------------------------------------------------------------
    |
    | Comma-separated list in RBAC_SUPER_ADMIN_EMAILS. These users receive
    | the "super-admin" role when AuthorizationSeeder runs.
    |
    */
    'super_admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('RBAC_SUPER_ADMIN_EMAILS', ''))
    ))),

];
