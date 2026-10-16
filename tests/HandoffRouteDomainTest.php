<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Register the package routes again, as the service provider would on boot,
 * with the current configuration.
 */
function reloadHandoffRoutes(): void
{
    Route::setRoutes(new RouteCollection);

    require __DIR__.'/../routes/handoff.php';

    Route::getRoutes()->refreshNameLookups();
}

function signedHandoffPath(int|string $user, string $target): string
{
    return URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        ['user' => $user, 'target' => $target],
        absolute: false,
    );
}

it('registers the handoff route without a domain constraint by default', function () {
    expect(Route::getRoutes()->getByName('handoff')?->getDomain())->toBeNull();
});

it('constrains the handoff route to the configured domain', function () {
    config()->set('handoff.domain', 'auth.example.org');

    reloadHandoffRoutes();

    expect(Route::getRoutes()->getByName('handoff')?->getDomain())->toBe('auth.example.org');
});

it('ignores an empty domain', function () {
    config()->set('handoff.domain', '');

    reloadHandoffRoutes();

    expect(Route::getRoutes()->getByName('handoff')?->getDomain())->toBeNull();
});

it('serves the handoff route on the configured domain', function () {
    config()->set('handoff.domain', 'auth.example.org');

    reloadHandoffRoutes();

    $this->get('https://auth.example.org'.signedHandoffPath($this->testUser->getAuthIdentifier(), '/dashboard'))
        ->assertRedirect('/dashboard');

    expect(auth()->check())->toBeTrue();
});

it('does not serve the handoff route on other domains', function () {
    config()->set('handoff.domain', 'auth.example.org');

    reloadHandoffRoutes();

    $this->get('https://other.example.org'.signedHandoffPath($this->testUser->getAuthIdentifier(), '/dashboard'))
        ->assertNotFound();

    expect(auth()->check())->toBeFalse();
});
