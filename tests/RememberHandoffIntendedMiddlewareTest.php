<?php

use ClarkeWing\Handoff\HandoffIntended;
use ClarkeWing\Handoff\Http\Middleware\RememberHandoffIntended;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', RememberHandoffIntended::class])
        ->get('/login', fn () => 'target login form');
});

it('works with a middleware alias', function () {
    Route::middleware(['web', 'handoff.intended'])
        ->get('/login-aliased', fn () => 'target login form');

    $this->get('/login-aliased?intended=/members/42')
        ->assertOk()
        ->assertSessionHas(HandoffIntended::SESSION_KEY, '/members/42');
});

it('stores a valid intended path in the session', function () {
    $this->get('/login?intended='.urlencode('/members/42?tab=flights'))
        ->assertOk()
        ->assertSee('target login form')
        ->assertSessionHas(HandoffIntended::SESSION_KEY, '/members/42?tab=flights');
});

it('does nothing when no intended path is given', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSessionMissing(HandoffIntended::SESSION_KEY);
});

it('keeps a previously stored intended path when none is given', function () {
    $this->withSession([HandoffIntended::SESSION_KEY => '/members/42'])
        ->get('/login')
        ->assertOk()
        ->assertSessionHas(HandoffIntended::SESSION_KEY, '/members/42');
});

it('replaces a previously stored intended path', function () {
    $this->withSession([HandoffIntended::SESSION_KEY => '/members/42'])
        ->get('/login?intended=/members/43')
        ->assertOk()
        ->assertSessionHas(HandoffIntended::SESSION_KEY, '/members/43');
});

it('ignores intended paths that are not internal', function (string $intended) {
    $this->get('/login?intended='.urlencode($intended))
        ->assertOk()
        ->assertSessionMissing(HandoffIntended::SESSION_KEY);
})->with([
    'absolute URL' => 'https://malicious-site.com/members',
    'protocol-relative' => '//malicious-site.com/members',
    'backslash protocol-relative' => '/\\malicious-site.com',
    'relative without leading slash' => 'members/42',
    'empty' => '',
]);

it('ignores an intended parameter that is not a string', function () {
    $this->get('/login?intended[]=/members/42')
        ->assertOk()
        ->assertSessionMissing(HandoffIntended::SESSION_KEY);
});

it('does not require a session', function () {
    Route::middleware([RememberHandoffIntended::class])
        ->get('/stateless', fn () => 'stateless');

    $this->get('/stateless?intended=/members/42')
        ->assertOk()
        ->assertSee('stateless');
});
