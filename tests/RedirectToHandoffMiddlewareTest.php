<?php

use ClarkeWing\Handoff\Http\Middleware\RedirectToHandoff;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('handoff.routes', [
        'dashboard' => '/remote/dashboard',
    ]);

    config()->set('handoff.target_host', 'https://remote.app');
    config()->set('handoff.auth.ttl', 300);
});

it('works with a middleware alias', function () {
    Route::middleware(['handoff:/remote/path'])
        ->get('/handoff-explicit', fn () => 'should never reach here');

    $response = $this->actingAs($this->testUser)->get('/handoff-explicit');

    $response->assertRedirect();
    $response->assertDontSee('should never reach here');
    expect($response->headers->get('Location'))
        ->toStartWith('https://remote.app/handoff');
});

it('redirects authenticated user to explicit path', function () {
    Route::middleware([RedirectToHandoff::class.':/remote/path'])
        ->get('/handoff-explicit', fn () => 'should never reach here');

    $response = $this->actingAs($this->testUser)->get('/handoff-explicit');

    $response->assertRedirect();
    $response->assertDontSee('should never reach here');
    expect($response->headers->get('Location'))
        ->toStartWith('https://remote.app/handoff');
});

it('redirects authenticated user based on route name mapping', function () {
    Route::middleware([RedirectToHandoff::class])
        ->get('/handoff-routed', fn () => 'should never reach here')
        ->name('dashboard');

    $response = $this->actingAs($this->testUser)->get('/handoff-routed');

    $response->assertRedirect();
    $response->assertDontSee('should never reach here');
    expect($response->headers->get('Location'))
        ->toStartWith('https://remote.app/handoff');
});

it('aborts with 401 when user is not authenticated', function () {
    Route::middleware([RedirectToHandoff::class.':/remote/path'])
        ->get('/handoff-unauth', fn () => 'should never reach here');

    $this->get('/handoff-unauth')
        ->assertStatus(401);
});

it('passes through if no path and route is not mapped', function () {
    Route::middleware([RedirectToHandoff::class])
        ->get('/handoff-pass', fn () => 'passed')
        ->name('nonexistent');

    $this->actingAs($this->testUser)->get('/handoff-pass')
        ->assertOk()
        ->assertSee('passed');
});
