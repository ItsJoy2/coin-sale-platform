<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $this->merchantId =
            config('services.geteway.merchant_id');

        $this->merchantSecret =
            config('services.geteway.merchant_secret');
    }


    /*
    |--------------------------------------------------------------------------
    | Recursive Sort
    |--------------------------------------------------------------------------
    */

    protected function sortRecursive(
        array $array
    ): array {

        foreach ($array as &$value) {

            if (is_array($value)) {
                $value = $this->sortRecursive($value);
            }
        }

        ksort($array);

        return $array;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Signature
    |--------------------------------------------------------------------------
    */

    protected function generateSignature(
        array $payload = []
    ): array {

        $merchantId = $this->merchantId;

        $timestamp = time();


        unset(
            $payload['merchant_id'],
            $payload['timestamp'],
            $payload['signature']
        );


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


    /*
    |--------------------------------------------------------------------------
    | POST Payload
    |--------------------------------------------------------------------------
    */

    public function payload(
        array $payload = []
    ): array {

        return array_merge(
            $payload,
            $this->generateSignature($payload)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET Auth
    |--------------------------------------------------------------------------
    */

    public function auth(
        array $payload = []
    ): array {

        return array_merge(
            $payload,
            $this->generateSignature($payload)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */

    public function client()
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout(50)
            ->retry(2, 100);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Invoice
    |--------------------------------------------------------------------------
    */

    public function createInvoice(
        array $payload
    ): Response {

        $requestPayload =
            $this->payload($payload);


        Log::info(
            '===== GATEWAY CREATE INVOICE =====',
            [
                'url' =>
                    $this->url .
                    '/api/v1/create-invoice',

                'payload' =>
                    $requestPayload,
            ]
        );


        return $this->client()->post(
            $this->url .
            '/api/v1/create-invoice',
            $requestPayload
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check Payment By TX Hash
    |--------------------------------------------------------------------------
    */

    public function checkPaymentByTxHash(
        string $txHash
    ): Response {

        $txHash = trim($txHash);


        if ($txHash === '') {

            throw new \InvalidArgumentException(
                'Transaction hash is required.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Signature must be generated using EXACTLY:
        |
        | txHash
        |
        */

        $params = $this->auth([
            'txHash' => $txHash,
        ]);


        $url =
            $this->url .
            '/api/v1/payments/' .
            rawurlencode($txHash);


        Log::info(
            '===== CHECKING GATEWAY PAYMENT =====',
            [
                'url'    => $url,
                'params' => $params,
                'txHash' => $txHash,
            ]
        );


        $response = $this->client()->get(
            $url,
            $params
        );


        Log::info(
            '===== GATEWAY PAYMENT RESPONSE =====',
            [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]
        );


        return $response;
    }
}
