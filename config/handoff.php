<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | These settings control how authentication is handled when transferring
    | a user session from one site to another using a signed URL.
    |
    */

    'auth' => [
        // The Authenticable model used in your application.
        'model' => App\Models\User::class, /** @phpstan-ignore class.notFound */

        // Lifetime (in seconds) of the signed URL used for authentication.
        // This helps ensure links expire quickly and remain secure.
        'ttl' => env('HANDOFF_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Target Host
    |--------------------------------------------------------------------------
    |
    | The base URL of the other application (legacy or new) that you want to
    | redirect users to. This will be prepended to any path in the route map.
    |
    */

    'target_host' => env('HANDOFF_TARGET_HOST', ''),

    /*
    |--------------------------------------------------------------------------
    | Route Mapping
    |--------------------------------------------------------------------------
    |
    | This defines which local named routes should redirect to which path on
    | the other site. Keys should match local route names, and values should
    | be relative paths on the remote app. You may repeat values if multiple
    | routes on this side map to a single target path.
    |
    */

    'routes' => [
        // 'dashboard' => '/home',
        // 'settings.profile' => '/account/info',
    ],
];
