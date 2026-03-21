<?php

declare(strict_types=1);

namespace Sburina\Whmcs;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as BaseProvider;

class UserProvider implements BaseProvider
{
    protected Whmcs $client;

    public function __construct()
    {
        $this->client = app('whmcs');
    }

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(mixed $identifier): ?WhmcsUser
    {
        $userAttributes = [];
        if (session()->has(config('whmcs.session_key'))) {
            $userAttributes = session()->get(config('whmcs.session_key'));
        } else {
            $res = (array) $this->client->sbGetClientsDetails(null, (int) $identifier);
            if (Arr::has($res, 'result') && $res['result'] === 'success') {
                $userAttributes = (array) ($res['client'] ?? []);
            }
        }

        return !empty($userAttributes) ? new WhmcsUser($userAttributes) : null;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        //
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?WhmcsUser
    {
        if (!isset($credentials['email'])) {
            return null;
        }

        $userAttributes = [];
        if (session()->has(config('whmcs.session_key'))) {
            $userAttributes = session()->get(config('whmcs.session_key'));
        } else {
            $res = (array) $this->client->sbGetClientsDetails($credentials['email']);
            if (Arr::has($res, 'result') && $res['result'] === 'success') {
                $userAttributes = (array) ($res['client'] ?? []);
            }
        }

        return !empty($userAttributes) ? new WhmcsUser($userAttributes) : null;
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['email'], $credentials['password'])) {
            return false;
        }

        $res = (array) $this->client->sbValidateLogin(
            $credentials['email'],
            $credentials['password']
        );

        if (Arr::has($res, 'result') && $res['result'] === 'success') {
            $retrieved = $this->retrieveByCredentials($credentials);
            if ($retrieved) {
                session()->put(config('whmcs.session_key'), $retrieved->getAttributes());
            }
            return true;
        }

        return false;
    }

    /**
     * Rehash the user's password if required.
     * No-op: WHMCS manages its own password hashing externally.
     *
     * Required by Laravel 11+ UserProvider contract.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        // WHMCS manages password hashing; nothing to do here.
    }
}
