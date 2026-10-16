<?php

namespace ClarkeWing\Handoff\Support;

use Spatie\Url\Url as Uri;

/**
 * Validates and normalises paths that must stay within an application,
 * guarding every redirect the package performs against open redirect abuse.
 */
class InternalPath
{
    /**
     * Return the given value when it is a safe internal path, or null otherwise.
     *
     * A safe path starts with exactly one "/" (so it cannot carry a scheme, a
     * host, or be protocol-relative) and contains no control characters.
     */
    public static function sanitize(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if ($value[0] !== '/') {
            return null;
        }

        if (isset($value[1]) && ($value[1] === '/' || $value[1] === '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            return null;
        }

        $parts = parse_url($value);

        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        return $value;
    }

    /**
     * Reduce a (possibly absolute) URL to its path, query and fragment,
     * returning null when the result is not a safe internal path.
     */
    public static function fromUrl(string $url): ?string
    {
        if (trim($url) === '') {
            return null;
        }

        $uri = Uri::fromString($url);

        $path = '/'.ltrim($uri->getPath(), '/');

        if ($uri->getQuery() !== '') {
            $path .= '?'.$uri->getQuery();
        }

        if ($uri->getFragment() !== '') {
            $path .= '#'.$uri->getFragment();
        }

        return static::sanitize($path);
    }
}
