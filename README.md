# Handoff

[![Code Quality](https://github.com/clarkewing/handoff/actions/workflows/code-quality.yml/badge.svg)](https://github.com/clarkewing/handoff/actions/workflows/code-quality.yml)
[![Tests](https://github.com/clarkewing/handoff/actions/workflows/tests.yaml/badge.svg)](https://github.com/clarkewing/handoff/actions/workflows/tests.yaml)

**Handoff** is a Laravel package that enables seamless, secure cross-application authentication via temporary signed URLs. It’s ideal for projects maintaining both a legacy and a modern Laravel app, and ensures users can be automatically logged in across both systems without friction.

---

## 🚀 Features

- 🔐 Secure authentication handoff via signed URLs
- 🔁 Bi-directional route mapping between apps
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

## 🔐 Security
This package uses Laravel’s temporarySignedRoute() mechanism. URLs are cryptographically signed and valid only for a limited time. You can customize the TTL or add rate limiting via the included service provider.

---

## 🤝 Contributing
Issues and PRs welcome! Please see CONTRIBUTING.md if contributing tests or features.

---

## 📜 License
Released under the MIT License.

---
