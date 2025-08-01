<?php

use ClarkeWing\Handoff\Actions\GenerateHandoffUrl;
use ClarkeWing\Handoff\Tests\Fixtures\UserWithCustomIdentifier;

beforeEach(function () {
    config()->set('handoff.target_host', 'https://remote.app');

    $this->action = new GenerateHandoffUrl;
});

it('throws an exception when neither a target path nor a source route are provided', function () {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('GenerateHandoffUrl requires either a target path or a source route.');

    $this->action->generate($this->testUser);
});

it('generates a signed URL with a target path', function () {
    $url = $this->action->generate($this->testUser, toPath: '/some/path');

    expect($url)
        ->toStartWith('https://remote.app/handoff')
        ->toHaveQueryParam('user', $this->testUser->getAuthIdentifier())
        ->toHaveQueryParam('target', '/some/path')
        ->toHaveQueryParam('signature')
        ->toHaveQueryParam('expires');
});

it('generates a signed URL using a mapped source route', function () {
    config()->set('handoff.routes', [
        'dashboard' => '/remote/dashboard',
    ]);

    $url = $this->action->generate($this->testUser, fromRoute: 'dashboard');

    expect($url)
        ->toStartWith('https://remote.app/handoff')
        ->toHaveQueryParam('user', $this->testUser->getAuthIdentifier())
        ->toHaveQueryParam('target', '/remote/dashboard')
        ->toHaveQueryParam('signature')
        ->toHaveQueryParam('expires');
});

it('throws an exception when a mapping is not found for a source route', function () {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('No handoff route mapping found for route [nonexistent].');

    $this->action->generate($this->testUser, fromRoute: 'nonexistent');
});

it('uses the default auth identifier when no custom identifier is provided', function () {
    expect($this->action->generate($this->testUser, toPath: '/some/path'))
        ->toHaveQueryParam('user', $this->testUser->getAuthIdentifier());
});

it('uses a custom user identifier when provided', function () {
    $userWithEmailIdentifier = UserWithCustomIdentifier::create(['email' => 'custom@example.com']);
    $handoffIdentifierName = $userWithEmailIdentifier->getHandoffIdentifierName();

    expect($this->action->generate($userWithEmailIdentifier, toPath: '/some/path'))
        ->toHaveQueryParam('user', $userWithEmailIdentifier->{$handoffIdentifierName});
});

it('respects custom TTL when provided', function () {
    expect($this->action->generate($this->testUser, toPath: '/some/path', ttl: 600))
        ->toExpireIn(600);
});

it('uses config TTL when no custom TTL is provided', function () {
    config()->set('handoff.auth.ttl', 450);

    expect($this->action->generate($this->testUser, toPath: '/some/path'))
        ->toExpireIn(450);
});

it('swaps the base URL to the target host', function () {
    expect($this->action->generate($this->testUser, toPath: '/some/path'))
        ->toStartWith('https://remote.app/handoff');
});

expect()->extend('toExpireIn', function (int $seconds, int $tolerance = 2) {
    $url = $this->value;

    $expiry = (int) getQueryParamValue($url, 'expires');

    return expect(abs($expiry - now()->addSeconds($seconds)->timestamp))
        ->toBeLessThan($tolerance);
});
