<?php

namespace ClarkeWing\Handoff\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Uri;
use RuntimeException;

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

        $signedUrl = URL::temporarySignedRoute(
            'handoff',
            now()->addSeconds($ttl ?? config()->integer('handoff.auth.ttl', 300)),
            [
                'user' => $this->getUserKey($user),
                'target' => $targetPath,
            ],
            absolute: false,
        );

        return $this->swapBaseUrl($signedUrl);
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
        $targetHost = Uri::of(config()->string('handoff.target_host'));

        $host = $targetHost->host();
        $scheme = $targetHost->scheme();

        if ($host === null || $scheme === null) {
            throw new \RuntimeException('Invalid handoff.target_host config: must include scheme and host');
        }

        return Uri::to($signedUrl)
            ->withHost($host)
            ->withScheme($scheme)
            ->value();
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
