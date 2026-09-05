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

        if ($txHash === '') {
            throw new RuntimeException('Transaction hash is required.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Check payment from Gateway
        |--------------------------------------------------------------------------
        */

        $params = PaymentGatewayService::auth([
            'txHash' => $txHash,
        ]);

        $response = PaymentGatewayService::client()->get(
            rtrim(config('payment_gateway.api_url'), '/')
                . "/api/v1/payments/{$txHash}",
            $params
        );

        Log::info('Purchase webhook gateway response', [
            'user_id' => $userId,
            'tx_hash' => $txHash,
            'http_status' => $response->status(),
            'response' => $response->json(),
            'raw_body' => $response->body(),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Gateway payment check failed: ' . $response->body()
            );
        }

        $payment = $response->json();

        /*
        |--------------------------------------------------------------------------
        | 2. Validate Gateway Response
        |--------------------------------------------------------------------------
        */

        if (($payment['status'] ?? false) !== true) {
            throw new RuntimeException(
                $payment['message'] ?? 'Invalid payment response.'
            );
        }

        $gatewayInvoiceId = trim(
            (string) ($payment['invoice_id'] ?? '')
        );

        $paymentStatus = strtolower(
            trim((string) ($payment['payment_status'] ?? ''))
        );

        $gatewayAmount = $payment['amount'] ?? null;

        $gatewayToken = strtoupper(
            trim((string) ($payment['token'] ?? ''))
        );

        if ($gatewayInvoiceId === '') {
            throw new RuntimeException(
                'Invoice ID not found in gateway response.'
            );
        }

        if ($gatewayAmount === null || $gatewayAmount === '') {
            throw new RuntimeException(
                'Payment amount not found in gateway response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Payment must be completed
        |--------------------------------------------------------------------------
        */

        if ($paymentStatus !== 'completed') {
            return [
                'status' => false,
                'message' => 'Payment not completed.',
                'payment_status' => $paymentStatus,
                'tx_hash' => $txHash,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Find Purchase
        |
        | Gateway gives us invoice_id.
        | Find the user's purchase using that invoice_id.
        |--------------------------------------------------------------------------
        */

        $purchase = Purchase::query()
            ->where('user_id', $userId)
            ->where('invoice_id', $gatewayInvoiceId)
            ->first();

        if (!$purchase) {
            throw new RuntimeException(
                'Purchase not found for this invoice.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Invoice verification
        |--------------------------------------------------------------------------
        */

        $purchaseInvoiceId = trim(
            (string) $purchase->invoice_id
        );

        if (!hash_equals($purchaseInvoiceId, $gatewayInvoiceId)) {
            throw new RuntimeException(
                'Invoice ID mismatch.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Amount normalization
        |
        | Gateway:
        |   10
        |   10.0
        |   10.00
        |   10.00000000
        |
        | All will be compared as:
        |   10.00000000
        |--------------------------------------------------------------------------
        */

        $purchaseAmount = (string) $purchase->payable_usdt;
        $receivedAmount = (string) $gatewayAmount;

        /*
        * Normalize both values to 8 decimal places.
        *
        * Example:
        * 10       -> 10.00000000
        * 10.0     -> 10.00000000
        * 10.00    -> 10.00000000
        * 10.0001  -> 10.00010000
        */

        $purchaseAmountNormalized = bcadd(
            $purchaseAmount,
            '0',
            8
        );

        $receivedAmountNormalized = bcadd(
            $receivedAmount,
            '0',
            8
        );

        Log::info('Purchase webhook amount comparison', [
            'purchase_id' => $purchase->id,
            'invoice_id' => $gatewayInvoiceId,
            'purchase_payable_usdt' => $purchaseAmount,
            'purchase_normalized' => $purchaseAmountNormalized,
            'gateway_amount' => $receivedAmount,
            'gateway_normalized' => $receivedAmountNormalized,
        ]);

        if (
            bccomp(
                $receivedAmountNormalized,
                $purchaseAmountNormalized,
                8
            ) !== 0
        ) {
            throw new RuntimeException(
                "Payment amount mismatch. Expected {$purchaseAmountNormalized} USDT, received {$receivedAmountNormalized} USDT."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Process everything atomically
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $purchase,
            $userId,
            $txHash,
            $receivedAmountNormalized,
            $gatewayToken,
            $payment
        ) {

            /*
            * Lock purchase
            */
            $purchase = Purchase::query()
                ->where('id', $purchase->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$purchase) {
                throw new RuntimeException(
                    'Purchase not found.'
                );
            }

            /*
            * Duplicate webhook protection
            */
            if ($purchase->status === 'completed') {

                return [
                    'status' => true,
                    'message' => 'Purchase already processed.',
                    'purchase_id' => $purchase->id,
                    'invoice_id' => $purchase->invoice_id,
                    'tx_hash' => $purchase->tx_hash,
                ];
            }

            /*
            * Lock user
            */
            $user = User::query()
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                throw new RuntimeException(
                    'User not found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Credit MIND
            |--------------------------------------------------------------------------
            */

            $oldMindBalance = (string) (
                $user->mind_balance ?? '0'
            );

            $totalMind = (string) $purchase->total_mind;

            $newMindBalance = bcadd(
                $oldMindBalance,
                $totalMind,
                8
            );

            $user->mind_balance = $newMindBalance;
            $user->save();

            /*
            |--------------------------------------------------------------------------
            | Complete Purchase
            |--------------------------------------------------------------------------
            */

            $purchase->update([
                'received_usdt' => $receivedAmountNormalized,
                'tx_hash' => $txHash,
                'status' => 'completed',
                'paid_at' => now(),
                'completed_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Purchase Transaction
            |--------------------------------------------------------------------------
            */

            $transaction = Transaction::query()
                ->where('purchase_id', $purchase->id)
                ->where('type', 'purchase')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'purchase_id' => $purchase->id,
                    'type' => 'purchase',
                    'amount_mind' => $purchase->total_mind,
                    'amount_usdt' => $purchase->usdt_amount,
                    'rate_applied' => $purchase->mind_price,
                    'description' => 'MIND purchase',
                    'created_at' => now(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Coupon Usage
            |--------------------------------------------------------------------------
            */

            if (!empty($purchase->coupon_code)) {

                $coupon = Coupon::query()
                    ->whereRaw(
                        'UPPER(code) = ?',
                        [strtoupper($purchase->coupon_code)]
                    )
                    ->lockForUpdate()
                    ->first();

                if ($coupon) {
                    $coupon->increment('used_count');
                }
            }

            Log::info('Purchase completed successfully', [
                'user_id' => $user->id,
                'purchase_id' => $purchase->id,
                'invoice_id' => $purchase->invoice_id,
                'tx_hash' => $txHash,
                'received_usdt' => $receivedAmountNormalized,
                'total_mind' => $purchase->total_mind,
                'old_mind_balance' => $oldMindBalance,
                'new_mind_balance' => $newMindBalance,
                'token' => $gatewayToken,
            ]);

            return [
                'status' => true,
                'message' => 'Purchase completed successfully.',
                'purchase_id' => $purchase->id,
                'invoice_id' => $purchase->invoice_id,
                'tx_hash' => $txHash,
                'received_usdt' => $receivedAmountNormalized,
                'total_mind' => $purchase->total_mind,
                'mind_balance' => $newMindBalance,
            ];
        });
    }

}
