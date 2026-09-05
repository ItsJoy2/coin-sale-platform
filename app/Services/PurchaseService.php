<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PurchaseService
{
    public function __construct(
        protected PaymentGatewayService $gateway,
        protected SettingService $settings
    ) {
    }

    /**
     * Create a new purchase.
     */
    public function createPurchase(
        User $user,
        string $usdtAmount,
        ?string $couponCode = null
    ): Purchase {

        try {

            if (bccomp($usdtAmount, '0', 8) <= 0) {
                throw new RuntimeException(
                    'Invalid USDT amount.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Original USDT Amount
            |--------------------------------------------------------------------------
            */

            $usdtAmount = bcadd(
                $usdtAmount,
                '0',
                8
            );

            /*
            |--------------------------------------------------------------------------
            | Get Purchase Slots
            |--------------------------------------------------------------------------
            */

            $slots = $this->settings->get(
                'purchase_slots',
                []
            );

            if (!is_array($slots) || empty($slots)) {
                throw new RuntimeException(
                    'Purchase slots are not configured.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Find Slot Using ORIGINAL USDT Amount
            |--------------------------------------------------------------------------
            */

            $selectedSlot = null;

            foreach ($slots as $slot) {

                $minUsd = (string) (
                    $slot['min_usd'] ?? 0
                );

                $maxUsd = (string) (
                    $slot['max_usd'] ?? 0
                );

                if (
                    bccomp(
                        $usdtAmount,
                        $minUsd,
                        8
                    ) >= 0 &&
                    bccomp(
                        $usdtAmount,
                        $maxUsd,
                        8
                    ) <= 0
                ) {
                    $selectedSlot = $slot;
                    break;
                }
            }

            if (!$selectedSlot) {
                throw new RuntimeException(
                    'The purchase amount does not match any purchase slot.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | MIND Price
            |--------------------------------------------------------------------------
            */

            $mindPrice = $this->settings->get('mind_price');

            if ($mindPrice === null || !is_numeric($mindPrice)) {
                throw new RuntimeException(
                    'MIND price is not configured.'
                );
            }

            $mindPrice = (string) $mindPrice;

            if ((float) $mindPrice <= 0) {
                throw new RuntimeException(
                    'MIND price is not configured.'
                );
            }
            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            $coupon = null;

            if (
                $couponCode !== null &&
                trim($couponCode) !== ''
            ) {

                $normalizedCouponCode =
                    strtoupper(trim($couponCode));

                $coupon = Coupon::query()
                    ->whereRaw(
                        'UPPER(code) = ?',
                        [$normalizedCouponCode]
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$coupon) {
                    throw new RuntimeException(
                        'Invalid coupon code.'
                    );
                }

                if (!$coupon->isValid()) {
                    throw new RuntimeException(
                        'This coupon is expired, inactive, or has reached its usage limit.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Discount
            |--------------------------------------------------------------------------
            */

            $discountAmount = '0.00000000';

            if ($coupon) {

                $discountPercentage =
                    (string) $coupon->discount_percentage;

                $discountAmount = bcdiv(
                    bcmul(
                        $usdtAmount,
                        $discountPercentage,
                        16
                    ),
                    '100',
                    8
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Payable USDT
            |--------------------------------------------------------------------------
            */

            $payableUsdt = bcsub(
                $usdtAmount,
                $discountAmount,
                8
            );

            if (
                bccomp(
                    $payableUsdt,
                    '0',
                    8
                ) <= 0
            ) {
                throw new RuntimeException(
                    'Payable amount must be greater than zero.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate MIND
            |
            | IMPORTANT:
            | MIND is calculated using ORIGINAL USDT amount.
            |--------------------------------------------------------------------------
            */

            $mindAmount = bcdiv(
                $usdtAmount,
                $mindPrice,
                8
            );

            /*
            |--------------------------------------------------------------------------
            | Slot Bonus
            |--------------------------------------------------------------------------
            */

            $bonusPercentage = (string) (
                $selectedSlot['bonus_percentage'] ?? 0
            );

            $bonusMind = bcdiv(
                bcmul(
                    $mindAmount,
                    $bonusPercentage,
                    16
                ),
                '100',
                8
            );

            /*
            |--------------------------------------------------------------------------
            | Total MIND
            |--------------------------------------------------------------------------
            */

            $totalMind = bcadd(
                $mindAmount,
                $bonusMind,
                8
            );

            /*
            |--------------------------------------------------------------------------
            | USDT Configuration
            |--------------------------------------------------------------------------
            */

            $usdtConfig = $this->settings->get('USDT', []);

            if (!is_array($usdtConfig)) {
                throw new RuntimeException('USDT configuration is invalid.');
            }

            $chainId = $usdtConfig['chain_id'] ?? null;
            $contractAddress = $usdtConfig['contract_address'] ?? null;

            if (!$chainId || !$contractAddress) {
                throw new RuntimeException('USDT configuration is incomplete.');
            }
            /*
            |--------------------------------------------------------------------------
            | Create Purchase
            |--------------------------------------------------------------------------
            */

            $purchase = DB::transaction(
                function () use (
                    $user,
                    $coupon,
                    $usdtAmount,
                    $payableUsdt,
                    $mindPrice,
                    $mindAmount,
                    $bonusPercentage,
                    $bonusMind,
                    $totalMind,
                    $selectedSlot
                ) {

                    return Purchase::create([
                        'user_id' => $user->id,

                        'coupon_code' =>
                            $coupon?->code,

                        'usdt_amount' =>
                            $usdtAmount,

                        'payable_usdt' =>
                            $payableUsdt,

                        'mind_price' =>
                            $mindPrice,

                        'mind_amount' =>
                            $mindAmount,

                        'bonus_percentage' =>
                            $bonusPercentage,

                        'bonus_mind' =>
                            $bonusMind,

                        'total_mind' =>
                            $totalMind,

                        'slot' =>
                            $selectedSlot['slot'] ?? null,

                        'status' =>
                            'pending',
                    ]);
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Webhook URL
            |--------------------------------------------------------------------------
            */

            $webhookUrl =
                url('/api/v1/webhook') .
                '?' .
                http_build_query([
                    'user_id' => $user->id,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Create Gateway Invoice
            |
            | IMPORTANT:
            | Gateway receives PAYABLE amount.
            |--------------------------------------------------------------------------
            */

            $gatewayPayload = [

                'webhook_url' =>
                    $webhookUrl,

                'chain_id' =>
                    $chainId,

                'type' =>
                    'token',

                'contract_address' =>
                    $contractAddress,

                'token_name' =>
                    'USDT',

                'amount' =>
                    $payableUsdt,
            ];

            $response =
                $this->gateway->createInvoice(
                    $gatewayPayload
                );

                // Log::info('Blockmaster Create Invoice Response', [
                //     'http_status' => $response->status(),
                //     'successful' => $response->successful(),
                //     'body' => $response->body(),
                //     'json' => $response->json(),
                //     'payload' => $gatewayPayload,
                // ]);

            if (!$response->successful()) {

                $purchase->update([
                    'status' => 'failed',
                    'failure_reason' =>
                        'Payment gateway invoice creation failed.',
                ]);

                throw new RuntimeException(
                    'Unable to create payment invoice.'
                );
            }

            $gatewayData = $response->json();

            if (
                !is_array($gatewayData) ||
                ($gatewayData['status'] ?? false) !== true
            ) {

                $purchase->update([
                    'status' => 'failed',
                    'failure_reason' =>
                        'Invalid payment gateway response.',
                ]);

                throw new RuntimeException(
                    'Unable to create payment invoice.'
                );
            }

            $invoiceId =
                $gatewayData['data']['invoice_id']
                ?? $gatewayData['invoice_id']
                ?? null;

            $paymentAddress =
                $gatewayData['data']['address']
                ?? $gatewayData['address']
                ?? null;

            if (!$invoiceId || !$paymentAddress) {

                $purchase->update([
                    'status' => 'failed',
                    'failure_reason' =>
                        'Payment gateway did not return invoice information.',
                ]);

                throw new RuntimeException(
                    'Invalid payment gateway response.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Save Gateway Information
            |--------------------------------------------------------------------------
            */

            $purchase->update([
                'invoice_id' =>$invoiceId,
                'payment_address' =>$paymentAddress,
            ]);

            return $purchase->fresh();

        } catch (Throwable $e) {

            Log::error(
                'Purchase Creation Error',
                [
                    'user_id' => $user->id ?? null,
                    'usdt_amount' => $usdtAmount,
                    'coupon_code' => $couponCode,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Process payment gateway webhook.
     */
    public function processWebhook(
        string $txHash,
        int $userId
    ): array {

        $txHash = trim($txHash);

        $purchase = Purchase::query()
            ->where('user_id', $userId)
            ->whereNotNull('invoice_id')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();


        if (!$purchase) {

            Log::warning('Webhook Purchase Not Found', [
                'user_id' => $userId,
                'tx_hash' => $txHash,
            ]);

            return [
                'status'  => false,
                'message' => 'Pending purchase not found.',
            ];
        }

        if ($purchase->status === 'completed') {

            return [
                'status'  => true,
                'message' => 'Payment already processed.',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'user_id'     => $purchase->user_id,
                    'invoice_id'  => $purchase->invoice_id,
                    'tx_hash'     => $purchase->tx_hash,
                    'status'      => $purchase->status,
                ],
            ];
        }

        $invoiceId = trim((string) $purchase->invoice_id);


        if (!$invoiceId) {

            Log::error('Purchase Invoice ID Missing', [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'tx_hash'     => $txHash,
            ]);

            return [
                'status'  => false,
                'message' => 'Purchase invoice ID is missing.',
            ];
        }

        try {

            $params = PaymentGatewayService::auth([
                'txHash' => $txHash,
            ]);


            $response = PaymentGatewayService::client()->get(
                rtrim(config('payment_gateway.api_url'), '/')
                    . "/api/v1/payments/{$txHash}",
                $params
            );

        } catch (Throwable $e) {

            Log::error('Webhook Payment Check Exception', [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'invoice_id'  => $invoiceId,
                'tx_hash'     => $txHash,
                'message'     => $e->getMessage(),
            ]);

            return [
                'status'  => false,
                'message' => 'Unable to verify payment.',
            ];
        }

        Log::info('Purchase Webhook Gateway Response', [
            'purchase_id' => $purchase->id,
            'user_id'     => $userId,
            'invoice_id'  => $invoiceId,
            'tx_hash'     => $txHash,
            'http_status' => $response->status(),
            'body'        => $response->body(),
            'json'        => $response->json(),
        ]);

        if (!$response->successful()) {

            Log::error('Purchase Webhook Gateway Request Failed', [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'invoice_id'  => $invoiceId,
                'tx_hash'     => $txHash,
                'http_status' => $response->status(),
                'body'        => $response->body(),
            ]);

            return [
                'status'  => false,
                'message' => 'Unable to verify payment.',
            ];
        }

        $payment = $response->json();


        if (!is_array($payment)) {

            return [
                'status'  => false,
                'message' => 'Invalid payment gateway response.',
            ];
        }

        if (($payment['status'] ?? false) !== true) {

            return [
                'status'  => false,
                'message' =>
                    $payment['message']
                    ?? 'Invalid payment response.',
            ];
        }

        $gatewayInvoiceId = $payment['invoice_id'] ?? null;

        $paymentStatus = strtolower(
            trim((string) ($payment['payment_status'] ?? ''))
        );

        $receivedAmount = $payment['amount'] ?? null;

        $token = $payment['token'] ?? null;

        if (!$gatewayInvoiceId) {

            Log::error('Gateway Invoice ID Missing', [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'tx_hash'     => $txHash,
                'gateway_response' => $payment,
            ]);

            return [
                'status'  => false,
                'message' => 'Invoice ID not found in payment response.',
            ];
        }

        if ((string) $gatewayInvoiceId !== (string) $invoiceId) {

            Log::warning('Webhook Invoice Mismatch', [
                'purchase_id'       => $purchase->id,
                'user_id'           => $userId,
                'purchase_invoice'  => $invoiceId,
                'gateway_invoice'   => $gatewayInvoiceId,
                'tx_hash'           => $txHash,
            ]);

            return [
                'status'  => false,
                'message' => 'Invoice ID mismatch.',
            ];
        }

        if ($paymentStatus !== 'completed') {

            Log::info('Payment Not Completed Yet', [
                'purchase_id'    => $purchase->id,
                'user_id'        => $userId,
                'invoice_id'     => $invoiceId,
                'tx_hash'        => $txHash,
                'payment_status' => $paymentStatus,
            ]);

            return [
                'status'         => false,
                'message'        => 'Payment is not completed yet.',
                'payment_status' => $paymentStatus,
            ];
        }

        if ($receivedAmount === null || $receivedAmount === '') {

            Log::error('Payment Amount Missing', [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'invoice_id'  => $invoiceId,
                'tx_hash'     => $txHash,
                'gateway_response' => $payment,
            ]);

            return [
                'status'  => false,
                'message' => 'Payment amount not found.',
            ];
        }


        $receivedAmount = (string) $receivedAmount;

        $expectedAmount = (string) $purchase->payable_usdt;


        if (
            bccomp(
                $receivedAmount,
                $expectedAmount,
                8
            ) !== 0
        ) {

            Log::warning('Webhook Payment Amount Mismatch', [
                'purchase_id'     => $purchase->id,
                'user_id'         => $userId,
                'invoice_id'      => $invoiceId,
                'tx_hash'         => $txHash,
                'expected_amount' => $expectedAmount,
                'received_amount' => $receivedAmount,
            ]);

            return [
                'status'  => false,
                'message' => 'Payment amount mismatch.',
                'expected_amount' => $expectedAmount,
                'received_amount' => $receivedAmount,
            ];
        }

        return DB::transaction(function () use (
            $purchase,
            $txHash,
            $receivedAmount,
            $userId
        ) {

            $purchase = Purchase::query()
                ->where('id', $purchase->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();


            if (!$purchase) {

                throw new RuntimeException(
                    'Purchase not found during transaction.'
                );
            }

            if ($purchase->status === 'completed') {

                return [
                    'status'  => true,
                    'message' => 'Payment already processed.',
                    'data' => [
                        'purchase_id' => $purchase->id,
                        'invoice_id'  => $purchase->invoice_id,
                        'tx_hash'     => $purchase->tx_hash,
                        'status'      => $purchase->status,
                    ],
                ];
            }

            $user = User::query()
                ->where('id', $purchase->user_id)
                ->lockForUpdate()
                ->first();


            if (!$user) {

                throw new RuntimeException(
                    'User not found.'
                );
            }


            $oldBalance = (string) (
                $user->mind_balance ?? '0'
            );

            $mindAmount = (string) (
                $purchase->total_mind ?? '0'
            );


            $newBalance = bcadd(
                $oldBalance,
                $mindAmount,
                8
            );


            $user->mind_balance = $newBalance;
            $user->save();

            $purchase->received_usdt = $receivedAmount;
            $purchase->tx_hash       = $txHash;
            $purchase->status        = 'completed';
            $purchase->paid_at       = now();
            $purchase->completed_at  = now();

            $purchase->save();


            $transaction = Transaction::query()
                ->where('purchase_id', $purchase->id)
                ->where('type', 'purchase')
                ->lockForUpdate()
                ->first();


            if (!$transaction) {

                Transaction::create([
                    'user_id'     => $user->id,
                    'purchase_id' => $purchase->id,

                    'type'        => 'purchase',

                    'amount_mind' => $purchase->total_mind,
                    'amount_usdt' => $purchase->usdt_amount,

                    'rate_applied' => $purchase->mind_price,

                    'description' => 'MIND purchase',

                    'created_at' => now(),
                ]);
            }

            if (!empty($purchase->coupon_code)) {

                $coupon = Coupon::query()
                    ->where('code', $purchase->coupon_code)
                    ->lockForUpdate()
                    ->first();


                if ($coupon) {
                    $coupon->increment('used_count');
                }
            }

            Log::info(
                'Purchase Webhook Completed Successfully',
                [
                    'purchase_id'   => $purchase->id,
                    'user_id'       => $user->id,
                    'invoice_id'    => $purchase->invoice_id,
                    'tx_hash'       => $txHash,
                    'received_usdt' => $receivedAmount,
                    'mind_amount'   => $purchase->total_mind,
                    'old_balance'   => $oldBalance,
                    'new_balance'   => $newBalance,
                ]
            );

            return [
                'status'  => true,
                'message' => 'Payment completed successfully.',

                'data' => [
                    'purchase_id'   => $purchase->id,
                    'user_id'       => $user->id,
                    'invoice_id'    => $purchase->invoice_id,
                    'tx_hash'       => $purchase->tx_hash,
                    'received_usdt' => $purchase->received_usdt,
                    'mind_amount'   => $purchase->total_mind,
                    'mind_balance'  => $user->mind_balance,
                    'status'        => $purchase->status,
                ],
            ];
        });
    }

}
