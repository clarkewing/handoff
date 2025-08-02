<?php

use ClarkeWing\Handoff\Tests\TestCase;
use Illuminate\Support\Str;

uses(TestCase::class)->in(__DIR__);

expect()->extend('toHaveQueryParam', function (string $key, mixed $expected = null) {
    $url = $this->value;

    expect($actual = getQueryParamValue($url, $key))
        ->not->toThrow(
            InvalidArgumentException::class,
            null,
            "Expected URL to contain query parameter '{$key}', but it was missing.",
        );

    // If an expected value is provided, we assert it matches
    if (func_num_args() > 1) {
        expect($expected)->toBe($actual);
    }

    return $this;
});

function getQueryParamValue(string $url, string $key): string|int|null
{
    $query = parse_url($url, PHP_URL_QUERY);

    parse_str($query ?? '', $params);

    if (! array_key_exists($key, $params)) {
        throw new InvalidArgumentException("The query parameter [{$key}] was not found in the provided URL.");
    }

    return ($value = $params[$key]) === ''
        ? null
        : (is_numeric($value) ? (int) $value : $value);
}

function laravelVersion(): int
{
    return (int) Str::before(app()->version(), '.');
}
