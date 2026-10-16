<?php

namespace ClarkeWing\Handoff;

use ClarkeWing\Handoff\Actions\GenerateHandoffUrl;
use ClarkeWing\Handoff\Support\InternalPath;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;

/**
 * Manages the origin-app path a guest was trying to reach before being sent
 * to this app to log in, and turns it into a signed handoff back to the origin
 * app once they have authenticated.
 */
class HandoffIntended
{
    public const SESSION_KEY = 'handoff.intended';

    public function __construct(
        protected Session $session,
        protected GenerateHandoffUrl $generateHandoffUrl,
    ) {}

    /**
     * Store the intended origin-app path, ignoring anything that is not an internal path.
     */
    public function remember(mixed $path): bool
    {
        if (($path = InternalPath::sanitize($path)) === null) {
            return false;
        }

        $this->session->put(self::SESSION_KEY, $path);

        return true;
    }

    /**
     * Retrieve the intended origin-app path without removing it from the session.
     */
    public function get(): ?string
    {
        return InternalPath::sanitize($this->session->get(self::SESSION_KEY));
    }

    /**
     * Retrieve the intended origin-app path and remove it from the session.
     */
    public function pull(): ?string
    {
        return InternalPath::sanitize($this->session->pull(self::SESSION_KEY));
    }

    /**
     * Remove the intended origin-app path from the session.
     */
    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Consume the intended origin-app path and return a signed handoff URL
     * authenticating the given user there, or null when nothing was stored.
     */
    public function pullUrl(Authenticatable $user, ?int $ttl = null): ?string
    {
        if (($path = $this->pull()) === null) {
            return null;
        }

        return $this->generateHandoffUrl->generate(user: $user, toPath: $path, ttl: $ttl);
    }
}
