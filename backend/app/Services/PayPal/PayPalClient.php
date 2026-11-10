<?php

declare(strict_types=1);

namespace App\Services\PayPal;

use App\Models\PurchaseIntent;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class PayPalClient
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $webhookId;

    public function __construct(array $config = [])
    {
        $this->baseUrl      = $config['base_url'] ?? (string) Config::get('paypal.base_url', 'https://api-m.sandbox.paypal.com');
        $this->clientId     = $config['client_id'] ?? (string) Config::get('paypal.client_id', 'mock_paypal_client_id');
        $this->clientSecret = $config['client_secret'] ?? (string) Config::get('paypal.client_secret', 'mock_paypal_client_secret');
        $this->webhookId    = $config['webhook_id'] ?? (string) Config::get('paypal.webhook_id', 'mock_paypal_webhook_id');
    }

    /**
     * Retrieves an active OAuth2 Bearer access token, using cache when available.
     */
    public function getAccessToken(): string
    {
        $cacheKey = (string) Config::get('paypal.token_cache_key', 'paypal_oauth_access_token');

        $cachedToken = Cache::get($cacheKey);
        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "PayPal OAuth token request failed: " . $response->body()
            );
        }

        $data = $response->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if ($accessToken === '') {
            throw new \RuntimeException("PayPal OAuth response did not contain an access_token.");
        }

        // Cache token with safe TTL margin
        $ttlSeconds = max(60, $expiresIn - 120);
        Cache::put($cacheKey, $accessToken, $ttlSeconds);

        return $accessToken;
    }

    /**
     * Creates a PayPal Orders v2 resource with intent="AUTHORIZE".
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function createOrder(PurchaseIntent $intent, array $options = []): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'intent'         => 'AUTHORIZE',
            'purchase_units' => [
                [
                    'reference_id' => "intent_{$intent->id}",
                    'custom_id'    => (string) $intent->id,
                    'description'  => "IntentGuard Protected Purchase: " . ($intent->product_name ?? 'Item'),
                    'amount'       => [
                        'currency_code' => $intent->currency,
                        'value'         => $intent->totalPriceDecimal(),
                    ],
                ],
            ],
            'application_context' => [
                'brand_name'          => 'IntentGuard Authorization',
                'landing_page'        => 'NO_PREFERENCE',
                'user_action'         => 'CONTINUE',
                'return_url'          => $options['return_url'] ?? 'https://intentguard.local/checkout/return',
                'cancel_url'          => $options['cancel_url'] ?? 'https://intentguard.local/checkout/cancel',
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException("PayPal order creation failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Authorizes payment for an approved PayPal order via Orders v2.
     * Returns authorization data including Payments v2 authorization ID.
     *
     * @return array<string, mixed>
     */
    public function authorizeOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/authorize");

        if (!$response->successful()) {
            throw new \RuntimeException("PayPal order authorization failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Captures an authorized payment via PayPal Payments v2.
     *
     * @return array<string, mixed>
     */
    public function captureAuthorization(
        string $authorizationId,
        ?int $amountCents = null,
        ?string $currency = null,
    ): array {
        $token = $this->getAccessToken();

        $payload = [];
        if ($amountCents !== null && $currency !== null) {
            $payload = [
                'amount' => [
                    'currency_code' => $currency,
                    'value'         => number_format($amountCents / 100, 2, '.', ''),
                ],
                'final_capture' => true,
            ];
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/payments/authorizations/{$authorizationId}/capture", $payload);

        if (!$response->successful()) {
            throw new \RuntimeException("PayPal payment capture failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Voids an authorized payment via PayPal Payments v2.
     *
     * @return array<string, mixed>
     */
    public function voidAuthorization(string $authorizationId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/payments/authorizations/{$authorizationId}/void");

        // Void returns 204 No Content on success
        if ($response->status() !== 204 && !$response->successful()) {
            throw new \RuntimeException("PayPal authorization void failed: " . $response->body());
        }

        return $response->json() ?? ['status' => 'VOIDED'];
    }

    /**
     * Cryptographically validates an incoming PayPal Webhook event signature.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $headers = [
            'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url'          => $request->header('PAYPAL-CERT-URL'),
            'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        // Fail fast if essential security headers are absent
        foreach ($headers as $key => $value) {
            if (empty($value)) {
                return false;
            }
        }

        try {
            $token = $this->getAccessToken();

            $verificationPayload = [
                'transmission_id'   => $headers['transmission_id'],
                'transmission_time' => $headers['transmission_time'],
                'cert_url'          => $headers['cert_url'],
                'auth_algo'         => $headers['auth_algo'],
                'transmission_sig'  => $headers['transmission_sig'],
                'webhook_id'        => $this->webhookId,
                'webhook_event'     => $request->all(),
            ];

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", $verificationPayload);

            if (!$response->successful()) {
                return false;
            }

            $result = $response->json();
            return ($result['verification_status'] ?? '') === 'SUCCESS';
        } catch (\Throwable) {
            return false;
        }
    }
}
