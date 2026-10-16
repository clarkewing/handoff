<?php

namespace ClarkeWing\Handoff\Actions;

use ClarkeWing\Handoff\HandoffIntended;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Spatie\Url\Url as Uri;

/**
 * Generates a signed handoff URL to authenticate a user on another app
 * and redirect them to a target path.
 */
class GenerateHandoffUrl
{
    public function generate(Authenticatable $user, ?string $toPath = null, ?string $fromRoute = null, ?int $ttl = null): string
    {
        if (is_null($toPath) && is_null($fromRoute)) {
            throw new RuntimeException('GenerateHandoffUrl requires either a target path or a source route.');
        }

        $targetPath = $toPath ?? $this->resolveMappedRoute($fromRoute);

        /** @var int $configTtl */
        $configTtl = config('handoff.auth.ttl', 300);

        $signedUrl = URL::temporarySignedRoute(
            'handoff',
            now()->addSeconds($ttl ?? $configTtl),
            [
                'user' => $this->getUserKey($user),
                'target' => $targetPath,
            ],
            absolute: false,
        );

        return $this->swapBaseUrl($signedUrl);
    }

    /**
     * Generate a signed handoff URL to the path a guest was trying to reach on
     * the origin app before being sent here to log in, consuming it from the
     * session. Returns null when no such path was stored.
     */
    public function forIntended(Authenticatable $user, ?int $ttl = null): ?string
    {
        return app(HandoffIntended::class)->pullUrl($user, $ttl);
    }

    /**
     * Resolve a mapped target path from a source route name.
     */
    protected function resolveMappedRoute(string $sourceRoute): string
    {
        /** @var array<string, string> $routes */
        $routes = config('handoff.routes');

        if (! isset($routes[$sourceRoute])) {
            throw new RuntimeException("No handoff route mapping found for route [$sourceRoute].");
        }

        return $routes[$sourceRoute];
    }

    /**
     * Swap the base URL to point to the target app.
     */
    protected function swapBaseUrl(string $signedUrl): string
    {
        /** @var string $targetHost */
        $targetHost = config('handoff.target_host');
        $targetHost = Uri::fromString($targetHost);

        $host = $targetHost->getHost();
        $scheme = $targetHost->getScheme();

        return (string) Uri::fromString($signedUrl)
            ->withHost($host)
            ->withScheme($scheme);
    }

    /**
     * Get the identifier to include in the signed URL.
     * Fallback to the default auth identifier if no custom handoff identifier is provided.
     */
    protected function getUserKey(Authenticatable $user): string
    {
        /** @var string|int $key */
        $key = method_exists($user, 'getHandoffIdentifierName')
            ? $user->{$user->getHandoffIdentifierName()}
            : $user->getAuthIdentifier();

        return (string) $key;
    }
}
