# Handoff

[![Code Quality](https://github.com/clarkewing/handoff/actions/workflows/code-quality.yml/badge.svg)](https://github.com/clarkewing/handoff/actions/workflows/code-quality.yml)
[![Tests](https://github.com/clarkewing/handoff/actions/workflows/tests.yaml/badge.svg)](https://github.com/clarkewing/handoff/actions/workflows/tests.yaml)

**Handoff** is a Laravel package that enables seamless, secure cross-application authentication via temporary signed URLs. It’s ideal for projects maintaining both a legacy and a modern Laravel app, and ensures users can be automatically logged in across both systems without friction.

---

## 🚀 Features

- 🔐 Secure authentication handoff via signed URLs
- 🔁 Bi-directional route mapping between apps
- 🚪 Guest flow: log in on one app, land on the page you wanted on the other
- ⏱️ Expirable links with configurable TTL
- ⚙️ Fully customizable user model & identifier
- 🌍 Shared, environment-agnostic logic — install on both apps

---

## 🧩 Requirements

To use Handoff, the package must be installed in both Laravel applications you wish to link and their User models must be kept in sync.

Handoff is compatible with applications using Laravel 8 and above, and requires PHP 8.0 or above.

---

## 📦 Installation

**Handoff** must be installed in both your origin app and the target app.

```bash
composer require clarkewing/handoff
```

In your origin app’s `.env` file, register the target host:

```bash
HANDOFF_TARGET_HOST="https://new.example.org"
```

---

## ⚙️ Configuration

Optionally, you can publish the config file using the following command.

```bash
php artisan vendor:publish --tag=handoff-config
```

**Note:** This is required if you want to use route mapping (see the Usage section).

These are the contents of the published config file:

```php
/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| These settings control how authentication is handled when transferring
| a user session from one site to another using a signed URL.
|
*/

'auth' => [
    // The Authenticable model used in your application.
    'model' => App\Models\User::class, /** @phpstan-ignore class.notFound */

    // Lifetime (in seconds) of the signed URL used for authentication.
    // This helps ensure links expire quickly and remain secure.
    'ttl' => env('HANDOFF_TTL', 300),
],

/*
|--------------------------------------------------------------------------
| Target Host
|--------------------------------------------------------------------------
|
| The base URL of the other application (legacy or new) that you want to
| redirect users to. This will be prepended to any path in the route map.
|
*/

'target_host' => env('HANDOFF_TARGET_HOST', ''),

/*
|--------------------------------------------------------------------------
| Login Path
|--------------------------------------------------------------------------
|
| The path of the login page on the target host. Guests of this app can be
| sent there (see the RedirectGuestToHandoffLogin middleware) carrying the
| path they were trying to reach, so that the target app can hand them
| back here once they have authenticated.
|
*/

'login_path' => env('HANDOFF_LOGIN_PATH', '/login'),

/*
|--------------------------------------------------------------------------
| Domain
|--------------------------------------------------------------------------
|
| The host on which this app's /handoff endpoint is served. Leave it null
| to register the endpoint without a domain constraint, so that it answers
| on every host the app serves. Set it when the app serves several hosts
| and the endpoint should only exist on one of them.
|
*/

'domain' => env('HANDOFF_DOMAIN'),

/*
|--------------------------------------------------------------------------
| Route Mapping
|--------------------------------------------------------------------------
|
| This defines which local named routes should redirect to which path on
| the other site. Keys should match local route names, and values should
| be relative paths on the remote app. You may repeat values if multiple
| routes on this side map to a single target path.
|
*/

'routes' => [
    // 'dashboard' => '/home',
    // 'settings.profile' => '/account/info',
],
```

---

## 🔧 Usage

### Target app

In the target app, **Handoff** automatically registers a `/handoff` endpoint via its service provider. This endpoint validates the signed URL, logs the user in, and redirects them to the intended destination.

The endpoint is registered without a domain constraint, so it answers on every host the app serves. If the app serves several hosts, set `HANDOFF_DOMAIN` (the `domain` config entry) to the one host the endpoint should exist on.

### Origin app

From the origin app, **Handoff** provides three ways of _handing off_ a request to the target application:

1. [Manually generating a signed Handoff URL](#manually-generating-a-signed-handoff-url)
2. [Automatically redirecting users via the `handoff` middleware and providing a target path](#automatically-redirecting-users-via-the-handoff-middleware-and-providing-a-target-path)
3. [Automatically redirecting users via the `handoff` middleware while using route mapping](#automatically-redirecting-users-via-the-handoff-middleware-while-using-route-mapping)

---

#### Manually generating a signed Handoff URL

**Handoff** supplies a `GenerateHandoffUrl` action class which can be used to manually create a Handoff URL.

```php
use App\Models\User;
use ClarkeWing\Handoff\Actions\GenerateHandoffUrl;

$url = resolve(GenerateHandoffUrl::class)->generate(
    user: $user,                   // required – the user to authenticate
    toPath: '/account/info',       // optional if using fromRoute
    fromRoute: 'settings.profile', // optional if using toPath
    ttl: 300                       // optional – expiration in seconds
);
```

**Note:** Either `toPath` or `fromRoute` must be provided, not both.

#### Automatically redirecting users via the `handoff` middleware and providing a target path

To simplify redirecting users, **Handoff** registers its `handoff` middleware. It accepts a target path to which users will be redirected.

```php
Route::get('/settings/profile', fn () => view('settings.profile'))
   ->middleware('handoff:/account/info')
```

#### Automatically redirecting users via the `handoff` middleware while using route mapping

For cases where you’d prefer to centrally manage your handoff origin routes and target paths, **Handoff** provides the possibility to use route to path mapping.

##### 1. Apply the `handoff` middleware to origin routes

You have a few options for this. See the Laravel documentation on [Registering Middleware](https://laravel.com/docs/12.x/middleware#registering-middleware).

##### 2. Setup route mapping in the config

After publishing **Handoff**’s config file, you can set up route mapping:

```php
// config/handoff.php

'routes' => [
    'dashboard' => '/home',
    'settings.profile' => '/account/info',
],
```

---

## 🚪 Guest flow: logging in on the other app

When login lives on only one of the two apps, a guest who hits a protected page on the other app has to be sent across to log in, and then brought back to the page they wanted. **Handoff** covers both legs of that trip without either app depending on the other's auth stack.

```
Origin app                                   Target app
──────────                                   ──────────
GET /members/42 (guest)
  └─ auth middleware stores url.intended
     and redirects to /login
GET /login  [handoff.login]
  └─ 302 ──────────────────────────────────▶ GET /login?intended=/members/42  [handoff.intended]
                                               └─ stores "handoff.intended" in the session
                                             POST /login (user authenticates)
                                               └─ HandoffIntended::pullUrl($user)
GET /handoff?user=…&target=/members/42 ◀──────── 302 to the signed handoff URL
  └─ logs the user in, redirects to /members/42
```

In the examples below, the *origin* app is the one whose login page is going away and the *target* app is the one that authenticates users. Each app's `HANDOFF_TARGET_HOST` points at the other app, exactly as for the authenticated handoff.

### Origin app: send guests to the target login page

Apply the `handoff.login` middleware (`RedirectGuestToHandoffLogin`) to the origin app's login route. Guests are redirected to `{HANDOFF_TARGET_HOST}{login_path}?intended={path}`, where `path` is the page they were trying to reach, taken from the `url.intended` session entry that Laravel's `auth` middleware stores. Authenticated users pass through untouched.

```php
Route::get('/login', fn () => view('auth.login'))
    ->middleware('handoff.login')
    ->name('login');
```

The middleware also accepts an explicit path, which takes precedence over the session:

```php
Route::get('/login', fn () => view('auth.login'))
    ->middleware('handoff.login:/dashboard');
```

To build the URL yourself, resolve the `GenerateHandoffLoginUrl` action:

```php
use ClarkeWing\Handoff\Actions\GenerateHandoffLoginUrl;

// Pulls url.intended from the session and reduces it to a relative path
$url = resolve(GenerateHandoffLoginUrl::class)->generate();

// Or pass the intended path explicitly
$url = resolve(GenerateHandoffLoginUrl::class)->generate('/members/42');
```

Only internal paths are forwarded: anything with a scheme or host, or a protocol-relative form such as `//evil.com`, is dropped and the bare login URL is produced instead. The login page path defaults to `/login` and can be changed through the `login_path` config entry or `HANDOFF_LOGIN_PATH`.

### Target app: remember the intended path and hand the user back

Apply the `handoff.intended` middleware (`RememberHandoffIntended`) to the target app's login route. It validates the `intended` query parameter and stores it in the session under the `handoff.intended` key. Invalid or missing values are ignored, so the middleware is safe to leave on the route permanently.

```php
Route::get('/login', [LoginController::class, 'create'])
    ->middleware('handoff.intended');
```

Once the user has authenticated, consume the stored path from your own post-login response. `HandoffIntended::pullUrl()` returns a signed handoff URL to the origin app for that path, clearing it from the session, or `null` when nothing was stored so you can fall back to your usual redirect:

```php
use ClarkeWing\Handoff\HandoffIntended;

$handoffUrl = resolve(HandoffIntended::class)->pullUrl($request->user());

return $handoffUrl
    ? redirect()->away($handoffUrl)
    : redirect()->intended('/dashboard');
```

`GenerateHandoffUrl::forIntended($user)` is a shortcut for the same call. `HandoffIntended` also exposes `remember()`, `get()`, `pull()` and `forget()` should you need finer control over the stored path.

---

## 🔐 Security

This package uses Laravel’s `temporarySignedRoute()` mechanism. URLs are cryptographically signed and valid only for a limited time. You can customize the TTL or add rate limiting via the included service provider.

Every redirect target the package handles (the `target` of a signed handoff URL and the `intended` path of the guest flow) must be an internal path: it has to start with a single `/` and may not carry a scheme or host. Anything else is replaced with `/` or dropped, which prevents open redirects.

---

## 🤝 Contributing

Issues and PRs welcome! Please see our [Contribution guidelines](CONTRIBUTING.md) if contributing tests or features.

---

## 📜 License

Released under the MIT License.

---
