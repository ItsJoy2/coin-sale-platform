<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\Models\Purchase;
use App\Services\PaymentGatewayService;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected PaymentGatewayService $paymentGateway
    ) {
    }

    /**
     * Create Purchase.
     */
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'usdt_amount' => ['required','numeric','gt:0',],
                'coupon_code' => ['nullable','string','max:50',],
            ]);

            $user = $request->user();

            $purchase =
                $this->purchaseService->createPurchase(
                    $user,
                    (string) $validated['usdt_amount'],
                    $validated['coupon_code'] ?? null
                );

            return response()->json([
                'status' => true,

                'message' =>
                    'Purchase created successfully.',

                'data' => [

                        'purchase_id' => $purchase->id,
                        'invoice_id' => $purchase->invoice_id,
                        'payment_address' => $purchase->payment_address,
                        'coupon_code' => $purchase->coupon_code,
                        'status' => $purchase->status,
                        'created_at' => $purchase->created_at,
            ],
            ], 201);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error(
                'Purchase Controller Error',
                [
                    'user_id' =>
                        $request->user()?->id,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),

                // 'message' =>
                //     'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function validateCoupon(Request $request)
    {
        try {

            $validated = $request->validate([
                'coupon_code' => ['required','string','max:50'],
            ]);

            $couponCode = strtoupper(
                trim($validated['coupon_code'])
            );

            $coupon = Coupon::query()
                ->whereRaw('UPPER(code) = ?',[$couponCode])
                ->first();

            if (!$coupon) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid coupon code.',
                ], 404);
            }


            if (!$coupon->isValid()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This coupon is expired, inactive, or has reached its usage limit.',
                ], 422);
            }

            return response()->json([
                'status' => true,
                'message' => 'Coupon is valid.',
                'data' => [
                    'coupon_code' => $coupon->code,
                    'discount_percentage' => $coupon->discount_percentage,
                ],
            ]);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error('Coupon Validation Error', [
                'coupon_code' => $request->input('coupon_code'),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to validate coupon.',
            ], 500);
        }
    }

    public function paymentStatus(string $invoiceId)
    {
        $invoiceId = trim($invoiceId);

        if ($invoiceId === '') {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice ID is required.',
                'data'    => null,
            ], 422);
        }

        try {

            Log::info('===== PAYMENT STATUS CHECK BY INVOICE =====', [
                'invoice_id' => $invoiceId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Purchase
            |--------------------------------------------------------------------------
            */

            $purchase = Purchase::query()
                ->where('invoice_id', $invoiceId)
                ->first();

            if (!$purchase) {

                return response()->json([
                    'status'  => false,
                    'message' => 'Purchase not found.',
                    'data'    => null,
                ], 404);
            }


            /*
            |--------------------------------------------------------------------------
            | TX Hash Not Available Yet
            |--------------------------------------------------------------------------
            */

            if (empty($purchase->tx_hash)) {

                return response()->json([
                    'status'  => true,
                    'message' => 'Payment is pending.',
                    'data'    => [
                        'payment_status' => 'pending',
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Check Gateway Payment
            |--------------------------------------------------------------------------
            */

            $paymentResponse =
                $this->paymentGateway->checkPaymentByTxHash(
                    $purchase->tx_hash
                );


            /*
            |--------------------------------------------------------------------------
            | Gateway Error
            |--------------------------------------------------------------------------
            */

            if (!$paymentResponse->successful()) {

                Log::error(
                    'Gateway payment status check failed.',
                    [
                        'invoice_id'  => $invoiceId,
                        'purchase_id' => $purchase->id,
                        'tx_hash'     => $purchase->tx_hash,
                        'http_status' => $paymentResponse->status(),
                        'response'    => $paymentResponse->body(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Frontend only needs pending/complete.
                | Gateway error হলে pending return করছি.
                |--------------------------------------------------------------------------
                */

                return response()->json([
                    'status'  => true,
                    'message' => 'Payment is pending.',
                    'data'    => [
                        'payment_status' => 'pending',
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Gateway Response
            |--------------------------------------------------------------------------
            */

            $res = $paymentResponse->json();


            $paymentStatus = strtolower(
                trim(
                    (string) (
                        $res['payment_status']
                        ?? $res['data']['payment_status']
                        ?? 'pending'
                    )
                )
            );


            Log::info(
                '===== PAYMENT STATUS RESULT =====',
                [
                    'invoice_id'     => $invoiceId,
                    'purchase_id'    => $purchase->id,
                    'tx_hash'        => $purchase->tx_hash,
                    'payment_status' => $paymentStatus,
                ]
            );

            if ($paymentStatus === 'completed') {

                return response()->json([
                    'status'  => true,
                    'message' => 'Payment completed.',
                    'data'    => [
                        'payment_status' => 'completed',
                    ],
                ]);
            }


            return response()->json([
                'status'  => true,
                'message' => 'Payment is pending.',
                'data'    => [
                    'payment_status' => 'pending',
                ],
            ]);

        } catch (\Throwable $e) {

            Log::error(
                '===== PAYMENT STATUS CHECK ERROR =====',
                [
                    'invoice_id' => $invoiceId,
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Frontend only needs pending/complete.
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status'  => false,
                'message' => 'Payment is pending.',
                'data'    => [
                    'payment_status' => 'pending',
                ],
            ]);
        }
    }


}
