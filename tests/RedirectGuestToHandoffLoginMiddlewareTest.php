<?php

use ClarkeWing\Handoff\Http\Middleware\RedirectGuestToHandoffLogin;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('handoff.target_host', 'https://remote.app');
});

it('works with a middleware alias', function () {
    Route::middleware(['web', 'handoff.login'])
        ->get('/login', fn () => 'origin login form');

    $this->get('/login')
        ->assertRedirect('https://remote.app/login')
        ->assertDontSee('origin login form');
});

it('redirects a guest to the target login page carrying the intended path from the session', function () {
    Route::middleware(['web', RedirectGuestToHandoffLogin::class])
        ->get('/login', fn () => 'origin login form');

    $this->withSession(['url.intended' => 'https://origin.app/members/42?tab=flights'])
        ->get('/login')
        ->assertRedirect('https://remote.app/login?intended=%2Fmembers%2F42%3Ftab%3Dflights')
        ->assertSessionMissing('url.intended');
});

it('chains from the auth middleware redirect to the target login page', function () {
    Route::middleware(['web', 'auth'])
        ->get('/members/42', fn () => 'protected');

    Route::middleware(['web', RedirectGuestToHandoffLogin::class])
        ->get('/login', fn () => 'origin login form')
        ->name('login');

    $this->get('/members/42')
        ->assertRedirect('/login');

    $this->get('/login')
        ->assertRedirect('https://remote.app/login?intended=%2Fmembers%2F42');
});

it('redirects a guest to the target login page carrying an explicit intended path', function () {
    Route::middleware(['web', RedirectGuestToHandoffLogin::class.':/members'])
        ->get('/login', fn () => 'origin login form');

    $this->withSession(['url.intended' => 'https://origin.app/ignored'])
        ->get('/login')
        ->assertRedirect('https://remote.app/login?intended=%2Fmembers');
});

it('redirects a guest to the bare target login page when nothing was intended', function () {
    Route::middleware(['web', RedirectGuestToHandoffLogin::class])
        ->get('/login', fn () => 'origin login form');

    $this->get('/login')
        ->assertRedirect('https://remote.app/login');
});

it('lets authenticated users through', function () {
    Route::middleware(['web', RedirectGuestToHandoffLogin::class])
        ->get('/login', fn () => 'origin login form');

    $this->actingAs($this->testUser)
        ->get('/login')
        ->assertOk()
        ->assertSee('origin login form');
});
