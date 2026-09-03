<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
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

                    'purchase_id' =>
                        $purchase->id,

                    'invoice_id' =>
                        $purchase->invoice_id,

                    'payment_address' =>
                        $purchase->payment_address,

                    'coupon_code' =>
                        $purchase->coupon_code,

                    'usdt_amount' =>
                        $purchase->usdt_amount,

                    'payable_usdt' =>
                        $purchase->payable_usdt,

                    'mind_price' =>
                        $purchase->mind_price,

                    'mind_amount' =>
                        $purchase->mind_amount,

                    'bonus_percentage' =>
                        $purchase->bonus_percentage,

                    'bonus_mind' =>
                        $purchase->bonus_mind,

                    'total_mind' =>
                        $purchase->total_mind,

                    'slot' =>
                        $purchase->slot,

                    'status' =>
                        $purchase->status,
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
}
