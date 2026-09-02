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
            config('services.blockmaster.url'),
            '/'
        );

        $this->merchantId =
            config('services.blockmaster.merchant_id');

        $this->merchantSecret =
            config('services.blockmaster.merchant_secret');
    }

    /**
     * Recursively sort array by key.
     */
    protected function sortRecursive(array $array): array
    {
        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $array[$key] =
                    $this->sortRecursive($value);
            }
        }

        ksort($array);

        return $array;
    }

    /**
     * Generate Gateway Signature.
     */
    protected function generateSignature(
        array $data
    ): string {

        $merchantId = $data['merchant_id'];
        $timestamp = $data['timestamp'];

        $payload = $data;

        unset(
            $payload['merchant_id'],
            $payload['timestamp'],
            $payload['signature'],
            $payload['_token'],
            $payload['_method']
        );

        $payload =
            $this->sortRecursive($payload);

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

        return hash_hmac(
            'sha256',
            $message,
            $this->merchantSecret
        );
    }

    /**
     * Create Invoice.
     */
    public function createInvoice(array $payload): Response
    {
        $payload['merchant_id'] =
            $this->merchantId;

        $payload['timestamp'] = time();

        $payload['signature'] =
            $this->generateSignature($payload);

        return Http::timeout(30)
            ->acceptJson()
            ->post(
                $this->url . '/api/v1/invoice/create',
                $payload
            );
    }

    /**
     * Check payment by transaction hash.
     */
    public function checkPaymentByTxHash(
        string $txHash
    ): Response {

        return Http::timeout(30)
            ->acceptJson()
            ->get(
                $this->url .
                '/api/v1/payments/' .
                urlencode($txHash)
            );
    }
}
