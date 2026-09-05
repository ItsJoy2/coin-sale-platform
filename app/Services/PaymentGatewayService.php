<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaymentGatewayService
{
    protected string $url;
    protected string $merchantId;
    protected string $merchantSecret;

    public function __construct()
    {
        $this->url = rtrim(
            config('services.geteway.url'),
            '/'
        );

        $this->merchantId = config(
            'services.geteway.merchant_id'
        );

        $this->merchantSecret = config(
            'services.geteway.merchant_secret'
        );
    }

    /**
     * Recursively sort array by key.
     */
    protected function sortRecursive(array $array): array
    {
        foreach ($array as &$value) {

            if (is_array($value)) {
                $value = $this->sortRecursive($value);
            }
        }

        ksort($array);

        return $array;
    }

    /**
     * Generate Gateway Signature.
     *
     * Signature message:
     *
     * merchant_id
     * +
     * timestamp
     * +
     * sorted JSON payload
     */
    protected function generateSignature(
        array $payload = []
    ): array {

        $merchantId = $this->merchantId;
        $timestamp  = time();

        /*
        |--------------------------------------------------------------------------
        | Remove authentication fields before signing
        |--------------------------------------------------------------------------
        */

        unset(
            $payload['merchant_id'],
            $payload['timestamp'],
            $payload['signature'],
            $payload['_token'],
            $payload['_method']
        );

        /*
        |--------------------------------------------------------------------------
        | Sort payload exactly like gateway
        |--------------------------------------------------------------------------
        */

        $payload = $this->sortRecursive($payload);

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );

        if ($jsonPayload === false) {
            throw new \RuntimeException(
                'Unable to encode gateway payload.'
            );
        }

        $message =
            $merchantId .
            $timestamp .
            $jsonPayload;

        $signature = hash_hmac(
            'sha256',
            $message,
            $this->merchantSecret
        );

        return [
            'merchant_id' => $merchantId,
            'timestamp'   => $timestamp,
            'signature'   => $signature,
        ];
    }

    /**
     * POST payload with authentication.
     */
    public function payload(
        array $payload = []
    ): array {

        return array_merge(
            $payload,
            $this->generateSignature($payload)
        );
    }

    /**
     * GET query parameters with authentication.
     */
    public function auth(
        array $payload = []
    ): array {

        return array_merge(
            $payload,
            $this->generateSignature($payload)
        );
    }

    /**
     * HTTP Client.
     */
    public function client()
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout(50)
            ->retry(2, 100);
    }

    /**
     * Create Invoice.
     */
    public function createInvoice(
        array $payload
    ): Response {

        $requestPayload = $this->payload($payload);

        return $this->client()->post(
            $this->url . '/api/v1/create-invoice',
            $requestPayload
        );
    }

    public function checkPaymentByTxHash(
        string $txHash
    ): Response {

        $txHash = trim($txHash);

        if ($txHash === '') {
            throw new \InvalidArgumentException(
                'Transaction hash is required.'
            );
        }

        $params = $this->auth([
            'txHash' => $txHash,
        ]);


        \Log::info('Gateway payment check request', [
            'url' => $this->url . '/api/v1/payments/' . urlencode($txHash),
            'params' => [
                'txHash' => $txHash,
                'merchant_id' => $params['merchant_id'],
                'timestamp' => $params['timestamp'],
                'signature' => $params['signature'],
            ],
        ]);

        return $this->client()->get(
            $this->url .
                '/api/v1/payments/' .
                urlencode($txHash),
            $params
        );
    }
}
