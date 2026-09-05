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
        return [
            'status'  => false,
            'message' => 'Transaction hash is required.',
        ];
    }

    Log::info('===== PURCHASE WEBHOOK PROCESS START =====', [
        'user_id' => $userId,
        'tx_hash' => $txHash,
    ]);


    /*
    |--------------------------------------------------------------------------
    | 1. Find purchase
    |--------------------------------------------------------------------------
    */

    $purchase = Purchase::query()
        ->where('user_id', $userId)
        ->where(function ($query) use ($txHash) {

            $query
                ->where('tx_hash', $txHash)
                ->orWhere(function ($q) {
                    $q
                        ->whereNull('tx_hash')
                        ->orWhere('tx_hash', '');
                });

        })
        ->whereIn('status', [
            'pending',
            'processing',
            'completed',
        ])
        ->orderBy('id', 'asc')
        ->first();


    /*
    |--------------------------------------------------------------------------
    | 2. Purchase not found
    |--------------------------------------------------------------------------
    */

    if (!$purchase) {

        Log::warning(
            'Purchase not found for webhook',
            [
                'user_id' => $userId,
                'tx_hash' => $txHash,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Purchase not found.',
            'data'    => [
                'user_id' => $userId,
                'txHash'  => $txHash,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Make sure TX hash is saved
    |--------------------------------------------------------------------------
    */

    if (
        empty($purchase->tx_hash)
        || $purchase->tx_hash !== $txHash
    ) {

        $purchase->update([
            'tx_hash' => $txHash,
        ]);

        /*
         * Refresh so latest DB data is available.
         */
        $purchase->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | 4. Already completed
    |--------------------------------------------------------------------------
    |
    | Very important for duplicate webhook.
    |
    */

    if ($purchase->status === 'completed') {

        Log::info(
            'Duplicate webhook - purchase already completed',
            [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'tx_hash'     => $txHash,
            ]
        );

        return [
            'status'  => true,
            'message' => 'Payment already processed.',
            'data'    => [
                'purchase_id' => $purchase->id,
                'txHash'      => $txHash,
                'status'      => 'completed',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 5. CHECK PAYMENT FROM GATEWAY
    |--------------------------------------------------------------------------
    */

    try {

        Log::info(
            'Checking payment gateway by TX hash',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
            ]
        );

        /*
         * IMPORTANT:
         *
         * Use injected PaymentGatewayService.
         *
         * Do NOT use:
         *
         * PaymentGatewayService::auth()
         *
         * here if your current service is instance based.
         */

        $response = $this->gateway
            ->checkPaymentByTxHash($txHash);

    } catch (Throwable $e) {

        Log::error(
            'Gateway payment check exception',
            [
                'purchase_id' => $purchase->id,
                'user_id'     => $userId,
                'tx_hash'     => $txHash,
                'message'     => $e->getMessage(),
            ]
        );

        return [
            'status'  => false,
            'message' => 'Unable to check payment from gateway.',
            'data'    => [
                'purchase_id' => $purchase->id,
                'txHash'      => $txHash,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 6. Log gateway HTTP response
    |--------------------------------------------------------------------------
    */

    Log::info(
        'Gateway payment response received',
        [
            'purchase_id' => $purchase->id,
            'tx_hash'     => $txHash,
            'http_status' => $response->status(),
            'body'        => $response->body(),
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | 7. HTTP ERROR
    |--------------------------------------------------------------------------
    */

    if (!$response->successful()) {

        Log::error(
            'Gateway returned HTTP error',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]
        );

        return [
            'status'  => false,
            'message' => 'Payment gateway returned an error.',
            'data'    => [
                'purchase_id' => $purchase->id,
                'txHash'      => $txHash,
                'http_status' => $response->status(),
                'gateway'     => $response->json(),
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 8. Parse response
    |--------------------------------------------------------------------------
    */

    $payment = $response->json();


    if (!is_array($payment)) {

        Log::error(
            'Invalid gateway JSON response',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
                'body'        => $response->body(),
            ]
        );

        return [
            'status'  => false,
            'message' => 'Invalid payment gateway response.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 9. Gateway status
    |--------------------------------------------------------------------------
    */

    if (
        isset($payment['status'])
        && !$payment['status']
    ) {

        return [
            'status'  => false,
            'message' =>
                $payment['message']
                ?? 'Payment gateway rejected the payment.',
            'data' => $payment,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 10. Extract payment data
    |--------------------------------------------------------------------------
    |
    | Expected:
    |
    | status
    | invoice_id
    | payment_status
    | amount
    | token
    |
    */

    $invoiceId =
        $payment['invoice_id']
        ?? data_get($payment, 'data.invoice_id');


    $paymentStatus =
        $payment['payment_status']
        ?? $payment['status']
        ?? data_get($payment, 'data.payment_status');


    $receivedAmount =
        $payment['amount']
        ?? $payment['received_amount']
        ?? data_get($payment, 'data.amount')
        ?? data_get($payment, 'data.received_amount');


    $token =
        $payment['token']
        ?? $payment['token_name']
        ?? data_get($payment, 'data.token')
        ?? data_get($payment, 'data.token_name');


    /*
    |--------------------------------------------------------------------------
    | 11. Log parsed data
    |--------------------------------------------------------------------------
    */

    Log::info(
        'Parsed gateway payment',
        [
            'purchase_id'   => $purchase->id,
            'tx_hash'       => $txHash,
            'invoice_id'    => $invoiceId,
            'payment_status'=> $paymentStatus,
            'amount'        => $receivedAmount,
            'token'         => $token,
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | 12. Invoice ID required
    |--------------------------------------------------------------------------
    */

    if (!$invoiceId) {

        Log::error(
            'Invoice ID missing from gateway response',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
                'response'    => $payment,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Invoice ID not found in payment response.',
            'data'    => $payment,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 13. Verify invoice belongs to purchase
    |--------------------------------------------------------------------------
    */

    if (
        !empty($purchase->invoice_id)
        && (string) $purchase->invoice_id !== (string) $invoiceId
    ) {

        Log::error(
            'Invoice mismatch',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
                'purchase_invoice_id' => $purchase->invoice_id,
                'gateway_invoice_id'   => $invoiceId,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Invoice ID does not match purchase.',
            'data'    => [
                'purchase_invoice_id' => $purchase->invoice_id,
                'gateway_invoice_id'  => $invoiceId,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 14. Verify payment status
    |--------------------------------------------------------------------------
    */

    $normalizedStatus = strtolower(
        trim((string) $paymentStatus)
    );

    $successfulStatuses = [
        'completed',
        'complete',
        'paid',
        'success',
        'successful',
        'confirmed',
        'approved',
    ];

    if (!in_array(
        $normalizedStatus,
        $successfulStatuses,
        true
    )) {

        Log::warning(
            'Payment is not completed',
            [
                'purchase_id'    => $purchase->id,
                'tx_hash'        => $txHash,
                'payment_status' => $paymentStatus,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Payment is not completed yet.',
            'data'    => [
                'purchase_id'    => $purchase->id,
                'txHash'          => $txHash,
                'payment_status' => $paymentStatus,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 15. Received amount required
    |--------------------------------------------------------------------------
    */

    if (
        $receivedAmount === null
        || $receivedAmount === ''
    ) {

        Log::error(
            'Received amount missing',
            [
                'purchase_id' => $purchase->id,
                'tx_hash'     => $txHash,
                'response'    => $payment,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Amount not found in payment response.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 16. Normalize amount
    |--------------------------------------------------------------------------
    */

    $expectedAmount = bcadd(
        (string) $purchase->amount,
        '0',
        8
    );

    $actualAmount = bcadd(
        (string) $receivedAmount,
        '0',
        8
    );


    /*
    |--------------------------------------------------------------------------
    | 17. Compare amount
    |--------------------------------------------------------------------------
    */

    if (
        bccomp(
            $actualAmount,
            $expectedAmount,
            8
        ) !== 0
    ) {

        Log::error(
            'Payment amount mismatch',
            [
                'purchase_id'   => $purchase->id,
                'tx_hash'       => $txHash,
                'expected'      => $expectedAmount,
                'received'      => $actualAmount,
            ]
        );

        return [
            'status'  => false,
            'message' => 'Payment amount does not match purchase amount.',
            'data'    => [
                'expected_amount' => $expectedAmount,
                'received_amount' => $actualAmount,
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | 18. ATOMIC PURCHASE COMPLETION
    |--------------------------------------------------------------------------
    */

    try {

        $result = DB::transaction(function () use (
            $purchase,
            $userId,
            $txHash,
            $invoiceId,
            $actualAmount,
            $token
        ) {

            /*
            |--------------------------------------------------------------------------
            | Lock purchase again
            |--------------------------------------------------------------------------
            */

            $lockedPurchase = Purchase::query()
                ->where('id', $purchase->id)
                ->lockForUpdate()
                ->first();


            if (!$lockedPurchase) {

                throw new \RuntimeException(
                    'Purchase not found during completion.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate webhook protection
            |--------------------------------------------------------------------------
            */

            if ($lockedPurchase->status === 'completed') {

                return [
                    'already_completed' => true,
                    'purchase'          => $lockedPurchase,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Lock user
            |--------------------------------------------------------------------------
            */

            $user = \App\Models\User::query()
                ->where('id', $userId)
                ->lockForUpdate()
                ->first();


            if (!$user) {

                throw new \RuntimeException(
                    'User not found.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Save TX + invoice
            |--------------------------------------------------------------------------
            */

            $lockedPurchase->tx_hash =
                $txHash;

            if (
                empty($lockedPurchase->invoice_id)
            ) {
                $lockedPurchase->invoice_id =
                    $invoiceId;
            }


            /*
            |--------------------------------------------------------------------------
            | Complete purchase
            |--------------------------------------------------------------------------
            */

            $lockedPurchase->status =
                'completed';


            /*
            |--------------------------------------------------------------------------
            | Credit MIND balance
            |--------------------------------------------------------------------------
            */

            $user->mind_balance =
                bcadd(
                    (string) ($user->mind_balance ?? 0),
                    (string) $actualAmount,
                    8
                );


            $user->save();


            /*
            |--------------------------------------------------------------------------
            | Save purchase
            |--------------------------------------------------------------------------
            */

            $lockedPurchase->save();


            /*
            |--------------------------------------------------------------------------
            | Create transaction only once
            |--------------------------------------------------------------------------
            */

            $transaction = \App\Models\Transaction::query()
                ->where('user_id', $user->id)
                ->where(function ($query) use (
                    $txHash
                ) {

                    $query
                        ->where('tx_hash', $txHash)
                        ->orWhere(
                            'reference',
                            $txHash
                        );

                })
                ->lockForUpdate()
                ->first();


            if (!$transaction) {

                $transaction =
                    \App\Models\Transaction::create([
                        'user_id' => $user->id,

                        'type' =>
                            'deposit',

                        'method' =>
                            'Purchase',

                        'wallet' =>
                            'MIND',

                        'amount' =>
                            $actualAmount,

                        'status' =>
                            'Approved',

                        'tx_hash' =>
                            $txHash,

                        'reference' =>
                            $txHash,

                        'description' =>
                            'MIND purchase payment',
                    ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            if (
                !empty($lockedPurchase->coupon_id)
            ) {

                $coupon =
                    \App\Models\Coupon::query()
                        ->where(
                            'id',
                            $lockedPurchase->coupon_id
                        )
                        ->lockForUpdate()
                        ->first();

                if ($coupon) {

                    $coupon->increment(
                        'used_count'
                    );
                }
            }


            return [
                'already_completed' => false,
                'purchase'          => $lockedPurchase,
                'transaction'       => $transaction,
                'user_balance'      => $user->mind_balance,
            ];
        });


        /*
        |--------------------------------------------------------------------------
        | 19. Already completed
        |--------------------------------------------------------------------------
        */

        if (
            $result['already_completed']
        ) {

            return [
                'status'  => true,
                'message' => 'Payment already processed.',
                'data'    => [
                    'purchase_id' =>
                        $result['purchase']->id,

                    'txHash' =>
                        $txHash,

                    'status' =>
                        'completed',
                ],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 20. SUCCESS
        |--------------------------------------------------------------------------
        */

        Log::info(
            '===== PURCHASE PAYMENT COMPLETED =====',
            [
                'purchase_id' =>
                    $result['purchase']->id,

                'user_id' =>
                    $userId,

                'tx_hash' =>
                    $txHash,

                'invoice_id' =>
                    $invoiceId,

                'amount' =>
                    $actualAmount,

                'token' =>
                    $token,

                'balance' =>
                    $result['user_balance'],
            ]
        );


        return [
            'status'  => true,
            'message' => 'Payment verified and purchase completed successfully.',
            'data'    => [
                'purchase_id' =>
                    $result['purchase']->id,

                'user_id' =>
                    $userId,

                'invoice_id' =>
                    $invoiceId,

                'txHash' =>
                    $txHash,

                'amount' =>
                    $actualAmount,

                'token' =>
                    $token,

                'status' =>
                    'completed',

                'balance' =>
                    $result['user_balance'],
            ],
        ];

    } catch (Throwable $e) {

        Log::error(
            'Purchase completion failed',
            [
                'purchase_id' =>
                    $purchase->id,

                'user_id' =>
                    $userId,

                'tx_hash' =>
                    $txHash,

                'message' =>
                    $e->getMessage(),

                'file' =>
                    $e->getFile(),

                'line' =>
                    $e->getLine(),
            ]
        );

        /*
         * IMPORTANT:
         *
         * TX hash was already committed before this.
         * Therefore even if completion fails,
         * tx_hash will remain in purchase table.
         */

        return [
            'status'  => false,
            'message' => 'Payment received but purchase completion failed.',
            'data'    => [
                'purchase_id' =>
                    $purchase->id,

                'txHash' =>
                    $txHash,
            ],
        ];
    }
}
}
