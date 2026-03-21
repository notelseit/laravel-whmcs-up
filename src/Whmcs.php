<?php

declare(strict_types=1);

namespace Sburina\Whmcs;

class Whmcs
{
    /**
     * Magic call to any WHMCS API method.
     *
     * @see https://developers.whmcs.com/api/api-index/
     */
    public function __call(string $name, array $args): array|object|null
    {
        $params = $args[0] ?? [];
        $params['action'] = $name;

        return (new Client())->post($params);
    }

    /**
     * Retrieve configured products matching provided criteria.
     *
     * @param  int|string|null  $pid  Product id or comma-separated list of ids
     * @param  int|null         $gid  Product group id
     * @param  string|null      $module  Server module name
     */
    public function sbGetProducts(
        int|string|null $pid = null,
        ?int $gid = null,
        ?string $module = null,
    ): array|object {
        return (new Client())->post([
            'action' => 'getProducts',
            'pid'    => $pid,
            'gid'    => $gid,
            'module' => $module,
        ]);
    }

    /**
     * Retrieve a list of clients.
     *
     * @param  int|null     $limitstart  Offset for returned data (default: 0)
     * @param  int|null     $limitnum    Number of records to return (default: 25)
     * @param  string|null  $sorting     Sort direction: ASC or DESC
     * @param  string|null  $search      Search term for email, name, or company
     * @param  string|null  $orderby     Field to sort by (id, firstname, lastname, companyname, email, groupid, datecreated, status)
     * @param  string|null  $status      Filter by status (Active, Inactive, Closed)
     */
    public function sbGetClients(
        ?int $limitstart = null,
        ?int $limitnum = null,
        ?string $sorting = null,
        ?string $search = null,
        ?string $orderby = null,
        ?string $status = null,
    ): array|object {
        return (new Client())->post([
            'action'     => 'getClients',
            'limitstart' => $limitstart,
            'limitnum'   => $limitnum,
            'sorting'    => $sorting,
            'search'     => $search,
            'orderby'    => $orderby,
            'status'     => $status,
        ]);
    }

    /**
     * Obtain the client details for a specific client.
     * Either email or clientid is required.
     */
    public function sbGetClientsDetails(
        ?string $email = null,
        ?int $clientid = null,
        bool $stats = false,
    ): array|object {
        return (new Client())->post([
            'action'   => 'getClientsDetails',
            'email'    => $email,
            'clientid' => $clientid,
            'stats'    => $stats,
        ]);
    }

    /**
     * Validate client login credentials.
     *
     * Note: WHMCS has flagged ValidateLogin for future deprecation.
     * Consider using CreateSsoToken for remote login functionality.
     *
     * @param  string  $email      Client or sub-account email address
     * @param  string  $password2  Password to validate
     */
    public function sbValidateLogin(string $email, string $password2): array|object
    {
        return (new Client())->post([
            'action'    => 'ValidateLogin',
            'email'     => $email,
            'password2' => $password2,
        ]);
    }

    /**
     * Create an SSO token for a client and return the redirect URL.
     * Replaces the old AutoAuth mechanism (removed in WHMCS 8.1).
     *
     * @param  int          $clientId        WHMCS client ID
     * @param  string|null  $destination     Where to redirect after login (e.g., 'sso:custom_redirect')
     * @param  string|null  $ssoRedirectPath Relative URL to redirect to in the client area
     * @param  int|null     $serviceId       Specific service ID to log into
     * @param  int|null     $domainId        Specific domain ID to log into
     * @return array|object Response containing 'access_token' and 'redirect_url' on success
     *
     * @see https://developers.whmcs.com/api-reference/createssotoken/
     */
    public function createSsoToken(
        int $clientId,
        ?string $destination = null,
        ?string $ssoRedirectPath = null,
        ?int $serviceId = null,
        ?int $domainId = null,
    ): array|object {
        $params = [
            'action'    => 'CreateSsoToken',
            'client_id' => $clientId,
        ];

        if ($destination !== null) {
            $params['destination'] = $destination;
        }
        if ($ssoRedirectPath !== null) {
            $params['sso_redirect_path'] = $ssoRedirectPath;
        }
        if ($serviceId !== null) {
            $params['service_id'] = $serviceId;
        }
        if ($domainId !== null) {
            $params['domain_id'] = $domainId;
        }

        return (new Client())->post($params);
    }

    /**
     * Get the SSO redirect URL for the currently authenticated user.
     * Replacement for the old getAutoLoginUrl() method.
     *
     * @param  string|null  $redirectPath  Relative URL in the WHMCS client area to redirect to
     * @return string The redirect URL, or '/' if not authenticated or SSO fails
     */
    public function getSsoUrl(?string $redirectPath = null): string
    {
        if (!auth()->check()) {
            return '/';
        }

        $user = auth()->user();
        if (!$user) {
            return '/';
        }

        $clientId = $user->userid ?? $user->id ?? null;
        if (!$clientId) {
            return '/';
        }

        $destination = $redirectPath ? 'sso:custom_redirect' : null;
        $result = (array) $this->createSsoToken((int) $clientId, $destination, $redirectPath);

        if (isset($result['result']) && $result['result'] === 'success' && !empty($result['redirect_url'])) {
            return $result['redirect_url'];
        }

        return '/';
    }

    /**
     * Redirect the authenticated user to WHMCS via SSO.
     * Replacement for the old redirectAutoLogin() method.
     *
     * @param  string|null  $redirectPath  Relative URL in the WHMCS client area
     */
    public function redirectSso(?string $redirectPath = null): \Illuminate\Http\RedirectResponse
    {
        return redirect($this->getSsoUrl($redirectPath));
    }

    /**
     * Generate the AutoAuth login URL for WHMCS.
     *
     * @deprecated Use getSsoUrl() instead. AutoAuth was removed in WHMCS 8.1.
     */
    public function getAutoLoginUrl(?string $goto = null): string
    {
        trigger_error(
            'getAutoLoginUrl() is deprecated. Use getSsoUrl() instead. AutoAuth was removed in WHMCS 8.1.',
            E_USER_DEPRECATED
        );

        if (!auth()->check()) {
            return '/';
        }

        $key = config('whmcs.autoauth.key');
        if (empty($key)) {
            return '/';
        }

        $whmcsurl  = rtrim(config('whmcs.url'), '/') . '/dologin.php';
        $timestamp = time();
        $user      = auth()->user();
        $email     = $user?->email;

        if (empty($email)) {
            return '/';
        }

        $hash = sha1($email . $timestamp . $key);

        return $whmcsurl
            . '?email=' . urlencode($email)
            . '&timestamp=' . $timestamp
            . '&hash=' . $hash
            . '&goto=' . urlencode($goto ?? config('whmcs.autoauth.goto'));
    }

    /**
     * Redirect to AutoAuth login URL.
     *
     * @deprecated Use redirectSso() instead. AutoAuth was removed in WHMCS 8.1.
     */
    public function redirectAutoLogin(?string $goto = null): \Illuminate\Http\RedirectResponse
    {
        trigger_error(
            'redirectAutoLogin() is deprecated. Use redirectSso() instead. AutoAuth was removed in WHMCS 8.1.',
            E_USER_DEPRECATED
        );

        return redirect($this->getAutoLoginUrl($goto));
    }
}
