<?php

declare(strict_types=1);

namespace Sburina\Whmcs;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Exception;

class Client
{
	/**
	 * Full URL to the WHMCS API endpoint.
	 */
	protected string $apiUrl;

	/**
	 * Whether to return results as arrays (true) or objects (false).
	 */
	protected bool $asArray;

	public function __construct()
	{
		$this->apiUrl = rtrim(config('whmcs.url'), '/') . '/includes/api.php';
		$this->asArray = (bool) config('whmcs.result_as_array', true);
	}

	/**
	 * Send a POST request to the WHMCS API.
	 */
	public function post(array $data): array|object
	{
		try {
			$authType = config('whmcs.auth.type');
			if (!in_array($authType, ['api', 'password'], true)) {
				return $this->errorResponse("Invalid WHMCS auth type: '{$authType}'. Must be 'api' or 'password'.");
			}

			$authConfig = config('whmcs.auth.' . $authType);
			if (!is_array($authConfig)) {
				return $this->errorResponse("WHMCS auth config for '{$authType}' is not configured.");
			}

			$formData = array_merge(
				$authConfig,
				['responsetype' => 'json'],
				$data
			);

			$pending = Http::timeout(config('whmcs.timeout', 30))
				->withUserAgent('sburina/laravel-whmcs-up');

			if (!config('whmcs.verify_ssl', true)) {
				$pending = $pending->withoutVerifying();
			}

			$accessKey = config('whmcs.api_access_key');
			if (!empty($accessKey)) {
				$pending = $pending->withQueryParameters(['accesskey' => $accessKey]);
			}

			$response = $pending->asForm()->post($this->apiUrl, $formData);
			$contents = $response->body();

			if (config('whmcs.use_floats', false)) {
				$contents = $this->convertFloats($contents);
			}

			$decoded = json_decode($contents, $this->asArray);

			if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
				return $this->errorResponse('Invalid JSON response from WHMCS: ' . json_last_error_msg());
			}

			return $decoded;
		} catch (ConnectionException $e) {
			return $this->errorResponse($e->getMessage());
		} catch (Exception $e) {
			return $this->errorResponse($e->getMessage());
		}
	}

	/**
	 * Build an error response matching the configured return type.
	 */
	protected function errorResponse(string $message): array|object
	{
		$error = ['result' => 'error', 'message' => $message];

		return $this->asArray ? $error : (object) $error;
	}

	/**
	 * Convert numeric string values to floats by parsing JSON and walking the structure.
	 */
	protected function convertFloats(string $contents): string
	{
		$data = json_decode($contents, true);
		if (!is_array($data)) {
			return $contents;
		}

		array_walk_recursive($data, function (&$value) {
			if (is_string($value) && preg_match('/^-?\d+\.\d+$/', $value)) {
				$value = (float) $value;
			}
		});

		return json_encode($data);
	}
}
