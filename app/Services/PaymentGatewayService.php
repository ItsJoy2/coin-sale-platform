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

        foreach ($array as $key => $value) {

            if (is_array($value)) {

                $array[$key] =
                    $this->sortRecursive($value);
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

        $merchantId =
            $this->merchantId;

        $timestamp =
            time();


        unset(
            $payload['merchant_id'],
            $payload['timestamp'],
            $payload['signature'],
            $payload['_token'],
            $payload['_method']
        );


        $payload =
            $this->sortRecursive($payload);


        $jsonPayload =
            json_encode(
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


        $signature =
            hash_hmac(
                'sha256',
                $message,
                $this->merchantSecret
            );


        return [
            'merchant_id' =>
                $merchantId,

            'timestamp' =>
                $timestamp,

            'signature' =>
                $signature,
        ];
    }


    public function auth(
        array $payload = []
    ): array {

        return array_merge(
            $payload,
            $this->generateSignature(
                $payload
            )
        );
    }


    public function client()
    {
        return Http::asJson()
            ->acceptJson()
            ->timeout(50)
            ->retry(
                2,
                100
            );
    }


    public function createInvoice(
        array $payload
    ): Response {

        $payload =
            $this->auth($payload);


        Log::info(
            'Creating gateway invoice',
            [
                'url' =>
                    $this->url .
                    '/api/v1/create-invoice',

                'payload' =>
                    $payload,
            ]
        );


        return $this->client()
            ->post(
                $this->url .
                '/api/v1/create-invoice',
                $payload
            );
    }

    public function checkPaymentByTxHash(
        string $txHash
    ): Response {

        $txHash =
            trim($txHash);

        $params = $this->auth([
            'txHash' =>
                $txHash,
        ]);


        $url =
            $this->url .
            '/api/v1/payments/' .
            urlencode($txHash);


        Log::info(
            '===== CHECKING GATEWAY PAYMENT =====',
            [
                'url' =>
                    $url,

                'params' =>
                    $params,

                'tx_hash' =>
                    $txHash,
            ]
        );


        return $this->client()
            ->get(
                $url,
                $params
            );
    }
}
