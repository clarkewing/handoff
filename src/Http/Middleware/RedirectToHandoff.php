<?php

namespace ClarkeWing\Handoff\Http\Middleware;

use ClarkeWing\Handoff\Actions\GenerateHandoffUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectToHandoff
{
    public function handle(Request $request, Closure $next, ?string $path = null): Response
    {
        if (! $user = Auth::user()) {
            abort(401, 'User must be authenticated to perform handoff.');
        }

        if ($path !== null) {
            return redirect()->away(app(GenerateHandoffUrl::class)->generate(
                user: $user,
                toPath: $path,
            ));
        }

        $routeName = Route::currentRouteName();

        if ($routeName && $this->isHandoffRoute($routeName)) {
            return redirect()->away(app(GenerateHandoffUrl::class)->generate(
                user: $user,
                fromRoute: $routeName,
            ));
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }

    protected function isHandoffRoute(string $routeName): bool
    {
        return array_key_exists($routeName, config()->array('handoff.routes'));
    }
}
