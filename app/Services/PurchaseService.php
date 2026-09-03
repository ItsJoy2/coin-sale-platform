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
                    'purchase_id' => $purchase->id,
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

                Log::info('Blockmaster Create Invoice Response', [
    'http_status' => $response->status(),
    'successful' => $response->successful(),
    'body' => $response->body(),
    'json' => $response->json(),
    'payload' => $gatewayPayload,
]);

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
                'invoice_id' =>
                    $invoiceId,

                'payment_address' =>
                    $paymentAddress,
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
        string $invoiceId,
        string $txHash,
        ?int $userId = null,
        ?int $purchaseId = null
    ): array {

        try {

            /*
            |--------------------------------------------------------------------------
            | Find Purchase
            |--------------------------------------------------------------------------
            */

            $purchaseQuery = Purchase::query()
                ->where('invoice_id', $invoiceId);

            if ($purchaseId !== null) {
                $purchaseQuery->where(
                    'id',
                    $purchaseId
                );
            }

            if ($userId !== null) {
                $purchaseQuery->where(
                    'user_id',
                    $userId
                );
            }

            $purchase = $purchaseQuery->first();

            if (!$purchase) {

                Log::warning(
                    'Purchase Webhook: Purchase Not Found',
                    [
                        'invoice_id' => $invoiceId,
                        'tx_hash' => $txHash,
                        'user_id' => $userId,
                        'purchase_id' => $purchaseId,
                    ]
                );

                return [
                    'status' => true,
                    'message' => 'Webhook received.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Already Completed
            |--------------------------------------------------------------------------
            */

            if ($purchase->status === 'completed') {

                return [
                    'status' => true,
                    'message' => 'Payment already processed.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Payment With Gateway
            |--------------------------------------------------------------------------
            */

            $response =
                $this->gateway->checkPaymentByTxHash(
                    $txHash
                );

            if (!$response->successful()) {

                Log::warning(
                    'Purchase Webhook: Gateway Check Failed',
                    [
                        'purchase_id' => $purchase->id,
                        'invoice_id' => $invoiceId,
                        'tx_hash' => $txHash,
                        'http_status' =>
                            $response->status(),
                    ]
                );

                return [
                    'status' => false,
                    'message' =>
                        'Unable to verify payment.',
                ];
            }

            $payment = $response->json();

            if (
                !is_array($payment) ||
                ($payment['status'] ?? false) !== true
            ) {

                return [
                    'status' => false,
                    'message' =>
                        'Invalid payment response.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Invoice ID
            |--------------------------------------------------------------------------
            */

            if (
                ($payment['invoice_id'] ?? null)
                !== $invoiceId
            ) {

                Log::warning(
                    'Purchase Webhook: Invoice Mismatch',
                    [
                        'purchase_id' =>
                            $purchase->id,

                        'expected_invoice_id' =>
                            $invoiceId,

                        'received_invoice_id' =>
                            $payment['invoice_id'] ?? null,
                    ]
                );

                return [
                    'status' => false,
                    'message' =>
                        'Payment verification failed.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Payment Status
            |--------------------------------------------------------------------------
            */

            if (
                ($payment['payment_status'] ?? null)
                !== 'completed'
            ) {

                return [
                    'status' => true,
                    'message' =>
                        'Payment is not completed yet.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Received Amount
            |--------------------------------------------------------------------------
            */

            $receivedAmount = bcadd(
                (string) (
                    $payment['amount'] ?? 0
                ),
                '0',
                8
            );

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | Compare received amount with PAYABLE USDT
            |--------------------------------------------------------------------------
            */

            $expectedAmount = bcadd(
                (string) $purchase->payable_usdt,
                '0',
                8
            );

            if (
                bccomp(
                    $receivedAmount,
                    $expectedAmount,
                    8
                ) !== 0
            ) {

                Log::warning(
                    'Purchase Webhook: Amount Mismatch',
                    [
                        'purchase_id' =>
                            $purchase->id,

                        'invoice_id' =>
                            $invoiceId,

                        'expected_amount' =>
                            $expectedAmount,

                        'received_amount' =>
                            $receivedAmount,

                        'tx_hash' =>
                            $txHash,
                    ]
                );

                return [
                    'status' => false,
                    'message' =>
                        'Payment amount does not match.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Complete Purchase Atomically
            |--------------------------------------------------------------------------
            */

            DB::transaction(
                function () use (
                    $purchase,
                    $receivedAmount,
                    $txHash
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Purchase
                    |--------------------------------------------------------------------------
                    */

                    $lockedPurchase =
                        Purchase::query()
                            ->where('id', $purchase->id)
                            ->lockForUpdate()
                            ->first();

                    if (!$lockedPurchase) {
                        throw new RuntimeException(
                            'Purchase not found.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Duplicate Credit
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $lockedPurchase->status ===
                        'completed'
                    ) {
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Lock User
                    |--------------------------------------------------------------------------
                    */

                    $user = User::query()
                        ->where(
                            'id',
                            $lockedPurchase->user_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$user) {
                        throw new RuntimeException(
                            'Purchase user not found.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update Purchase
                    |--------------------------------------------------------------------------
                    */

                    $lockedPurchase->update([
                        'received_usdt' =>
                            $receivedAmount,

                        'tx_hash' =>
                            $txHash,

                        'status' =>
                            'completed',

                        'paid_at' =>
                            now(),

                        'completed_at' =>
                            now(),
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Add MIND Balance
                    |--------------------------------------------------------------------------
                    */

                    $user->mind_balance = bcadd(
                        (string) $user->mind_balance,
                        (string) $lockedPurchase->total_mind,
                        8
                    );

                    $user->save();

                    /*
                    |--------------------------------------------------------------------------
                    | Create Purchase Transaction
                    |--------------------------------------------------------------------------
                    */

                    Transaction::create([
                        'user_id' =>
                            $user->id,

                        'purchase_id' =>
                            $lockedPurchase->id,

                        'type' =>
                            'purchase',

                        'amount_mind' =>
                            $lockedPurchase->total_mind,

                        'amount_usdt' =>
                            $lockedPurchase->usdt_amount,

                        'rate_applied' =>
                            $lockedPurchase->mind_price,

                        'description' =>
                            'MIND purchase',

                        'created_at' =>
                            now(),
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Coupon Usage
                    |--------------------------------------------------------------------------
                    |
                    | We use coupon_code snapshot.
                    | No coupon_id required.
                    |
                    */

                    if (
                        $lockedPurchase->coupon_code
                    ) {

                        $coupon =
                            Coupon::query()
                                ->where(
                                    'code',
                                    $lockedPurchase->coupon_code
                                )
                                ->lockForUpdate()
                                ->first();

                        /*
                        |--------------------------------------------------------------------------
                        | Coupon may have been deleted.
                        |
                        | Purchase remains valid because payable_usdt
                        | is already stored on purchase.
                        |--------------------------------------------------------------------------
                        */

                        if ($coupon) {

                            $coupon->increment(
                                'used_count'
                            );
                        }
                    }
                }
            );

            return [
                'status' => true,
                'message' =>
                    'Payment completed successfully.',
            ];

        } catch (Throwable $e) {

            Log::error(
                'Purchase Webhook Error',
                [
                    'invoice_id' => $invoiceId,
                    'tx_hash' => $txHash,
                    'user_id' => $userId,
                    'purchase_id' => $purchaseId,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            throw $e;
        }
    }
}
