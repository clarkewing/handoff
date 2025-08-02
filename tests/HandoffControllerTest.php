<?php

use ClarkeWing\Handoff\Tests\Fixtures\NonAuthenticatableUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\Url\Url as Uri;

beforeEach(function () {
    config()->set('handoff.target_host', 'https://remote.app');
});

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

it('accepts a signed URL generated from another hostname', function () {
    setUrlRoot('http://foo.com');

    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: '/dashboard',
    );

    expect($url)->toStartWith('https://remote.app');

    $this->get($url)
        ->assertRedirect('/dashboard');

    expect(auth())
        ->check()->toBeTrue()
        ->id()->toBe($this->testUser->getAuthIdentifier());
});

it('rejects expired signed URLs', function () {
    // Create an expired signed URL
    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: '/dashboard',
        expiry: now()->subMinute(),
    );

    $this->get($url)
        ->assertStatus(403)
        ->assertSee('Invalid or expired Handoff redirect URL');
});

it('requires a user parameter', function () {
    // Create a signed URL without a user parameter
    $url = generateHandoffUrl(
        target: '/dashboard',
    );

    $this->get($url)
        ->assertStatus(400);
});

it('returns 404 when user is not found', function () {
    // Create a signed URL with a non-existent user ID
    $url = generateHandoffUrl(
        user: 999,
        target: '/dashboard',
    );

    $this->get($url)
        ->assertStatus(404);
});

it('authenticates the user and redirects to the target path', function () {
    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: '/dashboard',
    );

    $this->get($url)
        ->assertRedirect('/dashboard');

    // Verify the user is authenticated
    expect(auth())
        ->check()->toBeTrue()
        ->id()->toBe($this->testUser->getAuthIdentifier());
});

it('sanitizes target paths to prevent open redirect vulnerabilities', function () {
    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: 'https://malicious-site.com',
    );

    // Should redirect to root instead of the external URL
    $this->get($url)
        ->assertRedirect('/');
});

it('defaults to root path when target is not provided', function () {
    // Create a signed URL without a target parameter
    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
    );

    $this->get($url)
        ->assertRedirect('/');
});

it('throws exception when configured user model does not exist', function () {
    // Configure a non-existent user model
    config()->set('handoff.auth.model', 'App\\NonExistentModel');

    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: '/dashboard',
    );

    $this->withoutExceptionHandling();

    expect(fn () => $this->get($url))
        ->toThrow(RuntimeException::class, 'Configured user model [App\\NonExistentModel] does not exist');
});

it('throws exception when configured user model does not implement Authenticatable', function () {
    // Configure the non-authenticatable user model
    config()->set('handoff.auth.model', NonAuthenticatableUser::class);

    $url = generateHandoffUrl(
        user: $this->testUser->getAuthIdentifier(),
        target: '/dashboard',
    );

    $this->withoutExceptionHandling();

    expect(fn () => $this->get($url))
        ->toThrow(RuntimeException::class, 'Configured user model [ClarkeWing\Handoff\Tests\Fixtures\NonAuthenticatableUser] must implement Authenticatable');
});

function setUrlRoot(string $root): void
{
    if (laravelVersion() < 10) {
        URL::forceRootUrl($root);
    } else {
        URL::useOrigin($root);
    }
}

function generateHandoffUrl(int|string|null $user = null, ?string $target = null, ?Carbon $expiry = null): string
{
    $signedUrl = URL::temporarySignedRoute(
        'handoff',
        $expiry ?? now()->addSeconds(config('handoff.auth.ttl')),
        array_filter([
            'user' => $user,
            'target' => $target,
        ]),
        absolute: false,
    );

    /** @var string $targetHost */
    $targetHost = config('handoff.target_host');
    $targetHost = Uri::fromString($targetHost);

    $host = $targetHost->getHost();
    $scheme = $targetHost->getScheme();

    return (string) Uri::fromString($signedUrl)
        ->withHost($host)
        ->withScheme($scheme);
}
