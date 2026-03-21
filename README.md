# Laravel WHMCS-UP

[![Latest Stable Version](https://poser.pugx.org/sburina/laravel-whmcs-up/v/stable)](https://packagist.org/packages/sburina/laravel-whmcs-up)
[![Total Downloads](https://poser.pugx.org/sburina/laravel-whmcs-up/downloads)](https://packagist.org/packages/sburina/laravel-whmcs-up)
[![License](https://poser.pugx.org/sburina/laravel-whmcs-up/license)](https://packagist.org/packages/sburina/laravel-whmcs-up)

WHMCS API client, user authentication provider, and SSO integration for Laravel.

## Version Compatibility

| Package Version | PHP    | Laravel       | WHMCS         | SSO Method     |
|-----------------|--------|---------------|---------------|----------------|
| **2.x**         | >= 8.2 | 10 - 13       | 8.1+          | CreateSsoToken |
| **1.x**         | >= 7.2 | 5.5 - 9       | 5.x - 8.0    | AutoAuth       |

## Installation

### Version 2.x (Laravel 10+)

```bash
composer require sburina/laravel-whmcs-up:^2.0
```

### Version 1.x (Laravel 5.5 - 9)

```bash
composer require sburina/laravel-whmcs-up:^1.1
```

### Publish Configuration

```bash
php artisan vendor:publish --provider="Sburina\Whmcs\WhmcsServiceProvider"
```

The package auto-discovers in Laravel 5.5+. No manual service provider registration needed.

## Configuration

Set these environment variables in your `.env` file:

```env
WHMCS_URL=https://your-whmcs-installation.com
WHMCS_AUTH_TYPE=api
WHMCS_API_ID=your-api-identifier
WHMCS_API_SECRET=your-api-secret
```

### Optional Variables

```env
# Access key to bypass WHMCS IP restrictions
WHMCS_API_ACCESS_KEY=

# Admin auth (deprecated by WHMCS — use API credentials instead)
# WHMCS_AUTH_TYPE=password
# WHMCS_ADMIN_USERNAME=admin
# WHMCS_ADMIN_PASSWORD=secret

# SSL verification (default: true; set to false for self-signed dev certs)
WHMCS_VERIFY_SSL=true

# Request timeout in seconds (default: 30, v2.x only)
WHMCS_TIMEOUT=30

# Session key for storing authenticated user data
WHMCS_SESSION_USER_KEY=whmcs_user

# AutoAuth key (v1.x only — AutoAuth was removed in WHMCS 8.1)
WHMCS_AUTOAUTH_KEY=
```

See `config/whmcs.php` for all options.

## Usage

### API Client

The package provides convenience methods with clean signatures:

```php
// Get products
\Whmcs::sbGetProducts($pid, $gid, $module);

// Get clients
\Whmcs::sbGetClients($limitstart, $limitnum, $sorting, $search);
// v2.x also supports: $orderby, $status parameters

// Get client details (email or clientid required)
\Whmcs::sbGetClientsDetails($email, $clientid, $stats);

// Validate login credentials
\Whmcs::sbValidateLogin($email, $password);
```

### Magic Calls

Any WHMCS API action can be called directly. The method name becomes the API action:

```php
\Whmcs::GetClientsProducts([
    'clientid' => 18122013
]);

\Whmcs::GetInvoice([
    'invoiceid' => 100001
]);

\Whmcs::AddOrder([
    'clientid'   => 1,
    'paymentmethod' => 'paypal',
    'pid'        => [1],
    'domain'     => ['example.com'],
]);
```

This works with any action listed in the [WHMCS API Index](https://developers.whmcs.com/api/api-index/), including custom API functions in your WHMCS installation.

### Without Facades

```php
$whmcs = app('whmcs');
$whmcs->GetInvoice(['invoiceid' => 100001]);
```

## Authentication

Authenticate Laravel users against your WHMCS user base. This is useful when your Laravel app has no local user database.

### Setup

**1.** In `config/auth.php`, set the provider driver:

```php
'providers' => [
    'users' => [
        'driver' => 'whmcs',
    ],
],
```

The `whmcs` auth driver is registered automatically by the package (v1.1+ and v2.x). No manual `Auth::provider()` registration needed.

**2.** Use Laravel's built-in auth as usual:

```php
// Routes
Auth::routes();

// Check authentication
auth()->check();
auth()->guest();

// Get the authenticated WHMCS user
$user = auth()->user(); // Returns WhmcsUser instance
$user->email;
$user->firstname;
$user->lastname;
$user->userid;
```

On successful login, user data from WHMCS is cached in the session. Subsequent `auth()->user()` calls use the cached data for the duration of the session.

## Single Sign-On (SSO)

Authenticated users in your Laravel app are **not** automatically logged into WHMCS. Use SSO to redirect them.

### Version 2.x — CreateSsoToken (WHMCS 8.1+)

```php
// Redirect to WHMCS client area (logged in)
return \Whmcs::redirectSso();

// Redirect to a specific WHMCS page
return \Whmcs::redirectSso('clientarea.php?action=invoices');

// Get just the SSO URL
$url = \Whmcs::getSsoUrl();
$url = \Whmcs::getSsoUrl('cart.php');
```

You can also call the underlying API directly:

```php
$result = \Whmcs::createSsoToken(
    clientId: 123,
    destination: 'sso:custom_redirect',
    ssoRedirectPath: 'clientarea.php?action=products'
);

// $result['redirect_url'] contains the one-time login URL
```

See [CreateSsoToken API Reference](https://developers.whmcs.com/api-reference/createssotoken/).

### Version 1.x — AutoAuth (WHMCS 5.x - 8.0)

AutoAuth must be enabled in WHMCS. See [WHMCS AutoAuth](https://docs.whmcs.com/AutoAuth).

```env
WHMCS_AUTOAUTH_KEY=your-autoauth-secret-key
```

```php
// Redirect to WHMCS (logged in)
return \Whmcs::redirectAutoLogin();

// Redirect to a specific page
return \Whmcs::redirectAutoLogin('cart.php');

// Get just the URL
$url = \Whmcs::getAutoLoginUrl();
```

## Migrating from v1.x to v2.x

### Requirements

- PHP 8.2+
- Laravel 10, 11, 12, or 13
- WHMCS 8.1+

### Breaking Changes

**SSO:** Replace AutoAuth calls with CreateSsoToken:

```php
// Before (v1.x)
return \Whmcs::redirectAutoLogin('cart.php');
$url = \Whmcs::getAutoLoginUrl();

// After (v2.x)
return \Whmcs::redirectSso('cart.php');
$url = \Whmcs::getSsoUrl();
```

The old methods still exist in v2.x but trigger deprecation notices and will be removed in v3.

**Config:** The default `session_key` changed from `user` to `whmcs_user`. If you rely on the default, either update your code or set `WHMCS_SESSION_USER_KEY=user` in your `.env`.

**SSL:** SSL verification is now enabled by default. If you were relying on the old behavior (verification disabled), set `WHMCS_VERIFY_SSL=false`.

**Auth provider registration:** If you previously registered the auth provider manually in `AuthServiceProvider`, you can remove that code — it's now handled automatically by the package.

### New Features in v2.x

- `sbGetClients()` supports `$orderby` and `$status` parameters (WHMCS 8.2+)
- Configurable request timeout via `WHMCS_TIMEOUT`
- Uses Laravel's HTTP client instead of raw Guzzle

## Support

[Please open an issue on GitHub](https://github.com/sburina/laravel-whmcs-up/issues)

## License

MIT License. See [LICENSE](https://github.com/sburina/laravel-whmcs-up/blob/master/LICENSE).
