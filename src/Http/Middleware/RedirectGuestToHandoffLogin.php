<?php

namespace ClarkeWing\Handoff\Http\Middleware;

use ClarkeWing\Handoff\Actions\GenerateHandoffLoginUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends guests to the target app's login page, carrying the path they were
 * trying to reach on this app. Authenticated users pass through untouched.
 */
class RedirectGuestToHandoffLogin
{
    public function handle(Request $request, Closure $next, ?string $intended = null): Response
    {
        if (Auth::guest()) {
            return redirect()->away(
                app(GenerateHandoffLoginUrl::class)->generate($intended)
            );
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
