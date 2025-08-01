<?php

use ClarkeWing\Handoff\Tests\Fixtures\NonAuthenticatableUser;
use Illuminate\Support\Facades\URL;

it('rejects missing signatures', function () {
    $this->get('/handoff?user=1&target=/dashboard')
        ->assertStatus(403)
        ->assertSee('Invalid or expired Handoff redirect URL');
});

it('rejects invalid signatures', function () {
    $this->get('/handoff?user=1&target=/dashboard&signature=foobar')
        ->assertStatus(403)
        ->assertSee('Invalid or expired Handoff redirect URL');
});

it('rejects expired signed URLs', function () {
    // Create an expired signed URL
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->subMinute(), // Expired 1 minute ago
        [
            'user' => $this->testUser->getAuthIdentifier(),
            'target' => '/dashboard',
        ]
    );

    $this->get($url)
        ->assertStatus(403)
        ->assertSee('Invalid or expired Handoff redirect URL');
});

it('requires a user parameter', function () {
    // Create a signed URL without a user parameter
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'target' => '/dashboard',
        ]
    );

    $this->get($url)
        ->assertStatus(400);
});

it('returns 404 when user is not found', function () {
    // Create a signed URL with a non-existent user ID
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => 999, // Non-existent user ID
            'target' => '/dashboard',
        ]
    );

    $this->get($url)
        ->assertStatus(404);
});

it('authenticates the user and redirects to the target path', function () {
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => $this->testUser->getAuthIdentifier(),
            'target' => '/dashboard',
        ]
    );

    $this->get($url)
        ->assertRedirect('/dashboard');

    // Verify the user is authenticated
    expect(auth())
        ->check()->toBeTrue()
        ->id()->toBe($this->testUser->getAuthIdentifier());
});

it('sanitizes target paths to prevent open redirect vulnerabilities', function () {
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => $this->testUser->getAuthIdentifier(),
            'target' => 'https://malicious-site.com',
        ]
    );

    // Should redirect to root instead of the external URL
    $this->get($url)
        ->assertRedirect('/');
});

it('defaults to root path when target is not provided', function () {
    // Create a signed URL without a target parameter
    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => $this->testUser->getAuthIdentifier(),
        ]
    );

    $this->get($url)
        ->assertRedirect('/');
});

it('throws exception when configured user model does not exist', function () {
    // Configure a non-existent user model
    config()->set('handoff.auth.model', 'App\\NonExistentModel');

    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => $this->testUser->getAuthIdentifier(),
            'target' => '/dashboard',
        ]
    );

    $this->withoutExceptionHandling();

    expect(fn () => $this->get($url))
        ->toThrow(RuntimeException::class, 'Configured user model [App\\NonExistentModel] does not exist');
});

it('throws exception when configured user model does not implement Authenticatable', function () {
    // Configure the non-authenticatable user model
    config()->set('handoff.auth.model', NonAuthenticatableUser::class);

    $url = URL::temporarySignedRoute(
        'handoff',
        now()->addMinutes(5),
        [
            'user' => 1,
            'target' => '/dashboard',
        ]
    );

    $this->withoutExceptionHandling();

    expect(fn () => $this->get($url))
        ->toThrow(RuntimeException::class, 'Configured user model [ClarkeWing\Handoff\Tests\Fixtures\NonAuthenticatableUser] must implement Authenticatable');
});
