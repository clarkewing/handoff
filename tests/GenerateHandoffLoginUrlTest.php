<?php

use ClarkeWing\Handoff\Actions\GenerateHandoffLoginUrl;

beforeEach(function () {
    config()->set('handoff.target_host', 'https://remote.app');

    $this->action = app(GenerateHandoffLoginUrl::class);
});

it('builds the target login URL with an explicit intended path', function () {
    expect($this->action->generate('/members/42?tab=flights'))
        ->toBe('https://remote.app/login?intended=%2Fmembers%2F42%3Ftab%3Dflights');
});

it('builds the target login URL from the intended URL stored in the session', function () {
    session()->put('url.intended', 'https://origin.app/members/42?tab=flights');

    expect($this->action->generate())
        ->toBe('https://remote.app/login?intended=%2Fmembers%2F42%3Ftab%3Dflights');
});

it('consumes the intended URL stored in the session', function () {
    session()->put('url.intended', 'https://origin.app/members/42');

    $this->action->generate();

    expect(session()->has('url.intended'))->toBeFalse();
});

it('builds a bare login URL when nothing was intended', function () {
    expect($this->action->generate())
        ->toBe('https://remote.app/login');
});

it('drops an explicit intended path that is not internal', function (string $intended) {
    expect($this->action->generate($intended))
        ->toBe('https://remote.app/login');
})->with([
    'absolute URL' => 'https://malicious-site.com/members',
    'protocol-relative' => '//malicious-site.com/members',
    'backslash protocol-relative' => '/\\malicious-site.com',
    'relative without leading slash' => 'members/42',
]);

it('drops a stored intended URL that cannot be made internal', function (mixed $intended) {
    session()->put('url.intended', $intended);

    expect($this->action->generate())
        ->toBe('https://remote.app/login');
})->with([
    'backslash protocol-relative' => '/\\malicious-site.com',
    'empty' => '',
    'not a string' => [['https://origin.app/members']],
]);

it('prefers an explicit intended path over the one stored in the session', function () {
    session()->put('url.intended', 'https://origin.app/members/42');

    expect($this->action->generate('/explicit'))
        ->toHaveQueryParam('intended', '/explicit');

    expect(session()->has('url.intended'))->toBeTrue();
});

it('uses the configured login path', function () {
    config()->set('handoff.login_path', '/auth/sign-in');

    expect($this->action->generate('/members'))
        ->toStartWith('https://remote.app/auth/sign-in?');
});

it('ignores a trailing slash on the target host', function () {
    config()->set('handoff.target_host', 'https://remote.app/');

    expect($this->action->generate())
        ->toBe('https://remote.app/login');
});
