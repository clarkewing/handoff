<?php

namespace ClarkeWing\Handoff\Actions;

use ClarkeWing\Handoff\Support\InternalPath;
use Illuminate\Contracts\Session\Session;
use Spatie\Url\Url as Uri;

/**
 * Builds the URL of the target app's login page for a guest of the origin app,
 * carrying the path they were trying to reach so the target app can hand them
 * back here once they have authenticated.
 */
class GenerateHandoffLoginUrl
{
    public function __construct(protected Session $session) {}

    /**
     * Generate the target app's login URL.
     *
     * When no intended path is given, the origin session's "url.intended" entry
     * (as stored by Laravel's guest redirect) is pulled from the session and
     * reduced to a relative path. Paths that are not internal are dropped
     * rather than forwarded.
     */
    public function generate(?string $intended = null): string
    {
        $intended = $intended === null
            ? $this->pullIntendedFromSession()
            : InternalPath::sanitize($intended);

        /** @var string $targetHost */
        $targetHost = config('handoff.target_host');

        /** @var string $loginPath */
        $loginPath = config('handoff.login_path', '/login');

        $url = Uri::fromString($targetHost)->withPath($loginPath);

        if ($intended !== null) {
            $url = $url->withQueryParameter('intended', $intended);
        }

        return (string) $url;
    }

    protected function pullIntendedFromSession(): ?string
    {
        $intended = $this->session->pull('url.intended');

        return is_string($intended) ? InternalPath::fromUrl($intended) : null;
    }
}
