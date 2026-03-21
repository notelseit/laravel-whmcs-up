<?php

return [
	/*
	|--------------------------------------------------------------------------
	| WHMCS URL
	|--------------------------------------------------------------------------
	|
	| Main URL to your WHMCS installation.
	|
	*/
	'url' => env('WHMCS_URL', 'https://localhost/whmcs'),

	/*
	|--------------------------------------------------------------------------
	| API Authentication
	|--------------------------------------------------------------------------
	|
	| Supported types: "api" (recommended), "password" (deprecated by WHMCS).
	|
	| @see https://developers.whmcs.com/api/authentication/
	|
	*/
	'auth' => [
		'type' => env('WHMCS_AUTH_TYPE', 'api'),

		'api' => [
			'identifier' => env('WHMCS_API_ID', ''),
			'secret'     => env('WHMCS_API_SECRET', ''),
		],

		'password' => [
			'username' => env('WHMCS_ADMIN_USERNAME', ''),
			'password' => env('WHMCS_ADMIN_PASSWORD', ''),
		],
	],

	/*
	|--------------------------------------------------------------------------
	| API Access Key
	|--------------------------------------------------------------------------
	|
	| Optional access key to bypass IP restrictions. Must match the key
	| defined in your WHMCS configuration.php file.
	|
	| @see https://developers.whmcs.com/api/access-control/
	|
	*/
	'api_access_key' => env('WHMCS_API_ACCESS_KEY', ''),

	/*
	|--------------------------------------------------------------------------
	| SSL Certificate Verification
	|--------------------------------------------------------------------------
	|
	| Verify the WHMCS server's SSL certificate. Should be true in production.
	| Set to false only for development with self-signed certificates.
	|
	*/
	'verify_ssl' => env('WHMCS_VERIFY_SSL', true),

	/*
	|--------------------------------------------------------------------------
	| Request Timeout
	|--------------------------------------------------------------------------
	|
	| Maximum number of seconds to wait for an API response.
	|
	*/
	'timeout' => env('WHMCS_TIMEOUT', 30),

	/*
	|--------------------------------------------------------------------------
	| AutoAuth (Deprecated — removed in WHMCS 8.1)
	|--------------------------------------------------------------------------
	|
	| Legacy AutoAuth settings. For WHMCS 8.1+, use CreateSsoToken via
	| Whmcs::getSsoUrl() or Whmcs::redirectSso() instead.
	|
	| @see https://developers.whmcs.com/api-reference/createssotoken/
	|
	*/
	'autoauth' => [
		'key'  => env('WHMCS_AUTOAUTH_KEY'),
		'goto' => 'clientarea.php?action=products',
	],

	/*
	|--------------------------------------------------------------------------
	| Session Key
	|--------------------------------------------------------------------------
	|
	| Session key used to store the WHMCS user record after authentication.
	|
	*/
	'session_key' => env('WHMCS_SESSION_USER_KEY', 'whmcs_user'),

	/*
	|--------------------------------------------------------------------------
	| Convert Decimal Strings to Floats
	|--------------------------------------------------------------------------
	|
	| WHMCS returns numbers as strings. Enable this to convert decimal
	| strings to floats in API responses.
	|
	*/
	'use_floats' => false,

	/*
	|--------------------------------------------------------------------------
	| Result Format
	|--------------------------------------------------------------------------
	|
	| true: return results as associative arrays
	| false: return results as stdClass objects
	|
	*/
	'result_as_array' => true,
];
