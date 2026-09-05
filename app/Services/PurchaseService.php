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
        string $invoiceId,
        string $txHash,
        int $userId
    ): array {
        $invoiceId = trim($invoiceId);
        $txHash    = trim($txHash);

        Log::info('===== PURCHASE WEBHOOK PROCESS START =====', [
            'invoice_id' => $invoiceId,
            'tx_hash'    => $txHash,
            'user_id'    => $userId,
        ]);

        if ($invoiceId === '') {
            throw new \Exception('invoice_id is required.');
        }

        if ($txHash === '') {
            throw new \Exception('txHash is required.');
        }

        if ($userId <= 0) {
            throw new \Exception('Invalid user_id.');
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Find Purchase
        |--------------------------------------------------------------------------
        */

        $purchase = Purchase::query()
            ->where('invoice_id', $invoiceId)
            ->where('user_id', $userId)
            ->first();

        if (!$purchase) {

            Log::error('Purchase not found for webhook.', [
                'invoice_id' => $invoiceId,
                'tx_hash'    => $txHash,
                'user_id'    => $userId,
            ]);

            throw new \Exception(
                'Purchase not found for this invoice.'
            );
        }

        Log::info('Purchase found.', [
            'purchase_id' => $purchase->id,
            'invoice_id'  => $purchase->invoice_id,
            'status'      => $purchase->status,
            'tx_hash'     => $purchase->tx_hash,
            'payable_usdt'=> $purchase->payable_usdt,
            'total_mind'  => $purchase->total_mind,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. Save TX Hash Immediately
        |--------------------------------------------------------------------------
        |
        | Gateway webhook এ txHash আসলেই প্রথমে purchase এ save করছি।
        |
        */

        if (
            empty($purchase->tx_hash) ||
            $purchase->tx_hash !== $txHash
        ) {

            $purchase->update([
                'tx_hash' => $txHash,
            ]);

            Log::info('TX hash saved to purchase.', [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
            ]);

            $purchase->refresh();
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Already Completed Check
        |--------------------------------------------------------------------------
        |
        | যদি webhook duplicate হয় এবং purchase already completed হয়,
        | তাহলে আবার balance credit করা হবে না।
        |
        */

        if ($purchase->status === 'completed') {

            Log::warning(
                'Purchase already completed. Duplicate webhook ignored.',
                [
                    'purchase_id' => $purchase->id,
                    'invoice_id'  => $invoiceId,
                    'tx_hash'     => $txHash,
                ]
            );

            return [
                'status'     => true,
                'message'    => 'Purchase already completed.',
                'invoice_id' => $invoiceId,
                'tx_hash'    => $txHash,
                'purchase_id'=> $purchase->id,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Verify Payment From Gateway
        |--------------------------------------------------------------------------
        */

        Log::info(
            'Checking payment from gateway.',
            [
                'tx_hash' => $txHash,
            ]
        );

        $response = $this->gateway->checkPaymentByTxHash(
            $txHash
        );

        Log::info(
            'Gateway payment response received.',
            [
                'tx_hash' => $txHash,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]
        );

        if (!$response->successful()) {

            throw new \Exception(
                'Gateway payment verification failed. HTTP ' .
                $response->status() .
                ': ' .
                $response->body()
            );
        }

        $payment = $response->json();

        if (!is_array($payment)) {
            throw new \Exception(
                'Invalid gateway payment response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Extract Gateway Response
        |--------------------------------------------------------------------------
        */

        $gatewayStatus =
            $payment['status']
            ?? $payment['data']['status']
            ?? false;

        if (!$gatewayStatus) {

            throw new \Exception(
                $payment['message']
                ?? $payment['data']['message']
                ?? 'Gateway returned unsuccessful payment status.'
            );
        }

        $gatewayInvoiceId =
            $payment['invoice_id']
            ?? $payment['data']['invoice_id']
            ?? null;

        $paymentStatus =
            $payment['payment_status']
            ?? $payment['data']['payment_status']
            ?? null;

        $actualAmount =
            $payment['amount']
            ?? $payment['received_amount']
            ?? $payment['data']['amount']
            ?? $payment['data']['received_amount']
            ?? null;

        $token =
            $payment['token']
            ?? $payment['token_name']
            ?? $payment['data']['token']
            ?? $payment['data']['token_name']
            ?? null;

        Log::info(
            'Parsed gateway payment.',
            [
                'purchase_id'       => $purchase->id,
                'gateway_invoice_id'=> $gatewayInvoiceId,
                'payment_status'    => $paymentStatus,
                'actual_amount'     => $actualAmount,
                'token'             => $token,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Verify Invoice ID
        |--------------------------------------------------------------------------
        */

        if (
            $gatewayInvoiceId !== null &&
            (string) $gatewayInvoiceId !== $invoiceId
        ) {

            Log::error(
                'Gateway invoice ID mismatch.',
                [
                    'expected_invoice_id' => $invoiceId,
                    'gateway_invoice_id'  => $gatewayInvoiceId,
                    'tx_hash'              => $txHash,
                ]
            );

            throw new \Exception(
                'Gateway invoice ID does not match purchase invoice.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Verify Payment Status
        |--------------------------------------------------------------------------
        */

        if (
            strtolower(
                trim((string) $paymentStatus)
            ) !== 'completed'
        ) {

            Log::warning(
                'Payment is not completed yet.',
                [
                    'purchase_id'    => $purchase->id,
                    'invoice_id'     => $invoiceId,
                    'tx_hash'        => $txHash,
                    'payment_status' => $paymentStatus,
                ]
            );

            return [
                'status'         => false,
                'message'        => 'Payment is not completed yet.',
                'invoice_id'     => $invoiceId,
                'tx_hash'        => $txHash,
                'payment_status' => $paymentStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Validate Amount
        |--------------------------------------------------------------------------
        */

        if ($actualAmount === null || $actualAmount === '') {

            throw new \Exception(
                'Payment amount not found in gateway response.'
            );
        }

        $expectedAmount = bcadd(
            (string) $purchase->payable_usdt,
            '0',
            8
        );

        $receivedAmount = bcadd(
            (string) $actualAmount,
            '0',
            8
        );

        Log::info(
            'Comparing payment amount.',
            [
                'purchase_id'    => $purchase->id,
                'expected_amount'=> $expectedAmount,
                'received_amount'=> $receivedAmount,
            ]
        );

        if (
            bccomp(
                $receivedAmount,
                $expectedAmount,
                8
            ) !== 0
        ) {

            Log::error(
                'Payment amount mismatch.',
                [
                    'purchase_id'     => $purchase->id,
                    'invoice_id'      => $invoiceId,
                    'tx_hash'         => $txHash,
                    'expected_amount' => $expectedAmount,
                    'received_amount' => $receivedAmount,
                ]
            );

            throw new \Exception(
                "Payment amount mismatch. Expected {$expectedAmount} USDT, received {$receivedAmount} USDT."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 9. Token Validation
        |--------------------------------------------------------------------------
        */

        if (
            $token !== null &&
            strtoupper(trim((string) $token)) !== 'USDT'
        ) {

            throw new \Exception(
                'Invalid payment token. Expected USDT.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 10. Atomic Completion
        |--------------------------------------------------------------------------
        */

        $result = DB::transaction(
            function () use (
                $purchase,
                $userId,
                $invoiceId,
                $txHash,
                $receivedAmount,
                $payment
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Purchase
                |--------------------------------------------------------------------------
                */

                $lockedPurchase = Purchase::query()
                    ->where('id', $purchase->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedPurchase) {
                    throw new \Exception(
                        'Purchase not found during transaction.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Protection
                |--------------------------------------------------------------------------
                */

                if ($lockedPurchase->status === 'completed') {

                    Log::warning(
                        'Purchase completed while processing webhook.',
                        [
                            'purchase_id' => $lockedPurchase->id,
                            'tx_hash'      => $txHash,
                        ]
                    );

                    return [
                        'already_completed' => true,
                        'purchase_id'       => $lockedPurchase->id,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | Lock User
                |--------------------------------------------------------------------------
                */

                $user = User::query()
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    throw new \Exception(
                        'User not found.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | MIND Amount
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                | এখানে USDT amount credit হবে না।
                |
                | Purchase এর total_mind credit হবে।
                |
                */

                $mindToCredit = bcadd(
                    (string) $lockedPurchase->total_mind,
                    '0',
                    8
                );

                if (
                    bccomp(
                        $mindToCredit,
                        '0',
                        8
                    ) <= 0
                ) {

                    throw new \Exception(
                        'Invalid MIND amount to credit.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Update User Balance
                |--------------------------------------------------------------------------
                */

                $oldBalance = bcadd(
                    (string) ($user->mind_balance ?? '0'),
                    '0',
                    8
                );

                $newBalance = bcadd(
                    $oldBalance,
                    $mindToCredit,
                    8
                );

                $user->mind_balance = $newBalance;
                $user->save();

                /*
                |--------------------------------------------------------------------------
                | Update Purchase
                |--------------------------------------------------------------------------
                */

                $lockedPurchase->update([
                    'tx_hash'       => $txHash,
                    'received_usdt' => $receivedAmount,
                    'paid_at'       => now(),
                    'completed_at'  => now(),
                    'status'        => 'completed',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Create Purchase Transaction
                |--------------------------------------------------------------------------
                |
                | Current transactions table অনুযায়ী fields ব্যবহার করা হচ্ছে।
                |
                */

                $transaction = Transaction::query()
                    ->where('purchase_id', $lockedPurchase->id)
                    ->where('type', 'purchase')
                    ->lockForUpdate()
                    ->first();

                if (!$transaction) {

                    $transaction = Transaction::create([
                        'user_id'      => $user->id,
                        'purchase_id'  => $lockedPurchase->id,
                        'type'         => 'purchase',
                        'amount_mind'  => $mindToCredit,
                        'amount_usdt'  => $receivedAmount,
                        'rate_applied' => $lockedPurchase->mind_price,
                        'description'  =>
                            'MIND purchase payment via gateway. TX: ' .
                            $txHash,
                        'created_at'   => now(),
                    ]);

                    Log::info(
                        'Purchase transaction created.',
                        [
                            'transaction_id' => $transaction->id,
                            'purchase_id'    => $lockedPurchase->id,
                            'user_id'        => $user->id,
                            'amount_mind'    => $mindToCredit,
                            'amount_usdt'    => $receivedAmount,
                        ]
                    );

                } else {

                    Log::warning(
                        'Purchase transaction already exists.',
                        [
                            'transaction_id' => $transaction->id,
                            'purchase_id'    => $lockedPurchase->id,
                        ]
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Save Gateway Response
                |--------------------------------------------------------------------------
                |
                | যদি purchases table-এ gateway_response column না থাকে,
                | এই অংশটি বাদ দিতে হবে।
                |
                */

                if (
                    \Schema::hasColumn(
                        'purchases',
                        'gateway_response'
                    )
                ) {

                    $lockedPurchase->update([
                        'gateway_response' => $payment,
                    ]);
                }

                Log::info(
                    'Purchase completed successfully.',
                    [
                        'purchase_id' => $lockedPurchase->id,
                        'user_id'     => $user->id,
                        'invoice_id'  => $invoiceId,
                        'tx_hash'     => $txHash,
                        'usdt'        => $receivedAmount,
                        'mind'        => $mindToCredit,
                        'old_balance' => $oldBalance,
                        'new_balance' => $newBalance,
                    ]
                );

                return [
                    'already_completed' => false,
                    'purchase_id'       => $lockedPurchase->id,
                    'transaction_id'    => $transaction->id,
                    'mind_credited'     => $mindToCredit,
                    'usdt_received'     => $receivedAmount,
                    'new_balance'       => $newBalance,
                ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | 11. Final Response
        |--------------------------------------------------------------------------
        */

        if ($result['already_completed'] ?? false) {

            return [
                'status'      => true,
                'message'     => 'Purchase already completed.',
                'invoice_id'  => $invoiceId,
                'tx_hash'     => $txHash,
                'purchase_id' => $result['purchase_id'],
            ];
        }

        Log::info(
            '===== PURCHASE WEBHOOK PROCESS SUCCESS =====',
            [
                'invoice_id'  => $invoiceId,
                'tx_hash'     => $txHash,
                'purchase_id' => $result['purchase_id'],
            ]
        );

        return [
            'status'          => true,
            'message'         => 'Payment verified and MIND credited successfully.',
            'invoice_id'      => $invoiceId,
            'tx_hash'         => $txHash,
            'purchase_id'     => $result['purchase_id'],
            'transaction_id'  => $result['transaction_id'],
            'usdt_received'   => $result['usdt_received'],
            'mind_credited'   => $result['mind_credited'],
            'new_balance'     => $result['new_balance'],
        ];
    }
}
