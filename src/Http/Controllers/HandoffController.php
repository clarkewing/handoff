<?php

namespace ClarkeWing\Handoff\Http\Controllers;

use ClarkeWing\Handoff\Support\InternalPath;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HandoffController
{
    public function __construct(protected Request $request) {}

    public function __invoke(): Response
    {
        $this->validateSignedURL();

        $user = $this->retrieveUser();

        Auth::login($user);

        return $this->redirect();
    }

    protected function validateSignedURL(): void
    {
        if (! URL::hasValidSignature($this->request, false)) {
            abort(403, 'Invalid or expired Handoff redirect URL.');
        }
    }

    protected function retrieveUser(): Authenticatable
    {
        if (! $userKey = $this->request->query('user')) {
            abort(400, 'Missing user identifier.');
        }

        /** @var class-string<Model&Authenticatable> $userModel */
        $userModel = config('handoff.auth.model');

        if (! class_exists($userModel)) {
            throw new RuntimeException("Configured user model [$userModel] does not exist.");
        }

        if (! ($userModelInstance = new $userModel) instanceof Authenticatable) {
            throw new RuntimeException("Configured user model [$userModel] must implement Authenticatable.");
        }

        $userIdentifierName = method_exists($userModelInstance, 'getHandoffIdentifierName')
            ? $userModelInstance->getHandoffIdentifierName()
            : $userModelInstance->getAuthIdentifierName();

        if (! is_string($userIdentifierName)) {
            throw new RuntimeException("[$userModel] getHandoffIdentifierName() must return a string.");
        }

        return $userModelInstance->newQuery()
            ->where($userIdentifierName, $userKey)
            ->firstOr(fn () => abort(404, 'User not found.'));
    }

    protected function redirect(): RedirectResponse
    {
        // Only allow internal paths to avoid open redirect abuse.
        $target = InternalPath::sanitize($this->request->query('target')) ?? '/';

        return response()->redirectTo($target);
    }
}
