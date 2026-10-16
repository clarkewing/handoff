<?php

use App\Models\User;

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
        'model' => User::class, /** @phpstan-ignore class.notFound */

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
    | Login Path
    |--------------------------------------------------------------------------
    |
    | The path of the login page on the target host. Guests of this app can be
    | sent there (see the RedirectGuestToHandoffLogin middleware) carrying the
    | path they were trying to reach, so that the target app can hand them
    | back here once they have authenticated.
    |
    */

    'login_path' => env('HANDOFF_LOGIN_PATH', '/login'),

    /*
    |--------------------------------------------------------------------------
    | Domain
    |--------------------------------------------------------------------------
    |
    | The host on which this app's /handoff endpoint is served. Leave it null
    | to register the endpoint without a domain constraint, so that it answers
    | on every host the app serves. Set it when the app serves several hosts
    | and the endpoint should only exist on one of them.
    |
    */

    'domain' => env('HANDOFF_DOMAIN'),

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
