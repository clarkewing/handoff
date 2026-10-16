<?php

use ClarkeWing\Handoff\Support\InternalPath;

it('accepts internal paths', function (string $path) {
    expect(InternalPath::sanitize($path))->toBe($path);
})->with([
    'root' => '/',
    'simple' => '/members',
    'nested with query' => '/members/42?tab=flights&page=2',
    'with fragment' => '/members/42#top',
    'with encoded characters' => '/search?q=a%20b',
]);

it('rejects paths that are not internal', function (mixed $path) {
    expect(InternalPath::sanitize($path))->toBeNull();
})->with([
    'null' => null,
    'empty string' => '',
    'array' => [['/members']],
    'absolute https URL' => 'https://malicious-site.com/members',
    'absolute http URL' => 'http://malicious-site.com',
    'scheme without slashes' => 'javascript:alert(1)',
    'protocol-relative' => '//malicious-site.com/members',
    'backslash protocol-relative' => '/\\malicious-site.com',
    'relative without leading slash' => 'members/42',
    'newline injection' => "/members\r\nLocation: https://malicious-site.com",
]);

it('reduces an absolute URL to its internal path', function (string $url, string $expected) {
    expect(InternalPath::fromUrl($url))->toBe($expected);
})->with([
    'host only' => ['https://origin.app', '/'],
    'host with trailing slash' => ['https://origin.app/', '/'],
    'path' => ['https://origin.app/members/42', '/members/42'],
    'path with query' => ['https://origin.app/members/42?tab=flights', '/members/42?tab=flights'],
    'path with query and fragment' => ['https://origin.app/members/42?tab=flights#top', '/members/42?tab=flights#top'],
    'already relative' => ['/members/42?tab=flights', '/members/42?tab=flights'],
    'protocol-relative keeps only the path' => ['//malicious-site.com/members', '/members'],
]);

it('returns null when a URL cannot be reduced to an internal path', function (string $url) {
    expect(InternalPath::fromUrl($url))->toBeNull();
})->with([
    'empty' => '',
    'whitespace' => '   ',
    'backslash protocol-relative' => '/\\malicious-site.com',
]);
