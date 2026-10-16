<?php

namespace ClarkeWing\Handoff\Http\Middleware;

use ClarkeWing\Handoff\HandoffIntended;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores the "intended" query parameter sent by the origin app in the session
 * so it can be turned into a handoff back to the origin app after login.
 */
class RememberHandoffIntended
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->query->has('intended')) {
            app(HandoffIntended::class)->remember($request->query('intended'));
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
