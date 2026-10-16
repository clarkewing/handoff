<?php

use ClarkeWing\Handoff\Actions\GenerateHandoffUrl;
use ClarkeWing\Handoff\HandoffIntended;

beforeEach(function () {
    config()->set('handoff.target_host', 'https://origin.app');

    $this->intended = app(HandoffIntended::class);
});

it('remembers an internal path', function () {
    expect($this->intended->remember('/members/42?tab=flights'))->toBeTrue();

    expect(session()->get(HandoffIntended::SESSION_KEY))->toBe('/members/42?tab=flights');
});

it('refuses to remember a path that is not internal', function (mixed $path) {
    expect($this->intended->remember($path))->toBeFalse();

    expect(session()->has(HandoffIntended::SESSION_KEY))->toBeFalse();
})->with([
    'absolute URL' => 'https://malicious-site.com/members',
    'protocol-relative' => '//malicious-site.com/members',
    'null' => null,
    'array' => [['/members']],
]);

it('gets the remembered path without consuming it', function () {
    $this->intended->remember('/members/42');

    expect($this->intended->get())->toBe('/members/42');
    expect($this->intended->get())->toBe('/members/42');
});

it('pulls the remembered path and clears it', function () {
    $this->intended->remember('/members/42');

    expect($this->intended->pull())->toBe('/members/42');
    expect($this->intended->pull())->toBeNull();
    expect(session()->has(HandoffIntended::SESSION_KEY))->toBeFalse();
});

it('forgets the remembered path', function () {
    $this->intended->remember('/members/42');

    $this->intended->forget();

    expect($this->intended->get())->toBeNull();
});

it('ignores a tampered session value that is not an internal path', function () {
    session()->put(HandoffIntended::SESSION_KEY, 'https://malicious-site.com');

    expect($this->intended->get())->toBeNull();
    expect($this->intended->pull())->toBeNull();
});

it('returns null when nothing was remembered', function () {
    expect($this->intended->get())->toBeNull();
    expect($this->intended->pullUrl($this->testUser))->toBeNull();
});

it('turns the remembered path into a signed handoff URL to the origin app', function () {
    $this->intended->remember('/members/42?tab=flights');

    $url = $this->intended->pullUrl($this->testUser);

    expect($url)
        ->toStartWith('https://origin.app/handoff')
        ->toHaveQueryParam('user', $this->testUser->getAuthIdentifier())
        ->toHaveQueryParam('target', '/members/42?tab=flights')
        ->toHaveQueryParam('signature')
        ->toHaveQueryParam('expires');
});

it('clears the remembered path once it has been turned into a handoff URL', function () {
    $this->intended->remember('/members/42');

    $this->intended->pullUrl($this->testUser);

    expect(session()->has(HandoffIntended::SESSION_KEY))->toBeFalse();
    expect($this->intended->pullUrl($this->testUser))->toBeNull();
});

it('passes a custom TTL through to the handoff URL', function () {
    $this->intended->remember('/members/42');

    $expiry = (int) getQueryParamValue($this->intended->pullUrl($this->testUser, ttl: 600), 'expires');

    expect(abs($expiry - now()->addSeconds(600)->timestamp))->toBeLessThan(2);
});

it('can be consumed through GenerateHandoffUrl::forIntended()', function () {
    $this->intended->remember('/members/42');

    $url = app(GenerateHandoffUrl::class)->forIntended($this->testUser);

    expect($url)
        ->toStartWith('https://origin.app/handoff')
        ->toHaveQueryParam('target', '/members/42');

    expect(app(GenerateHandoffUrl::class)->forIntended($this->testUser))->toBeNull();
});
