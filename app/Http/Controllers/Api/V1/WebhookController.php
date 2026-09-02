<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    /**
     * Payment Gateway Webhook.
     */
    public function handle(Request $request)
    {
        try {

            $invoiceId =
                $request->input('invoice_id');

            $txHash =
                $request->input('txHash');

            $userId =
                $request->query('user_id');

            $purchaseId =
                $request->query('purchase_id');

            if (
                !$invoiceId ||
                !$txHash
            ) {

                return response()->json([
                    'status' => false,

                    'message' =>
                        'Invalid webhook request.',
                ], 400);
            }

            $result =
                $this->purchaseService->processWebhook(
                    $invoiceId,
                    $txHash,
                    $userId
                        ? (int) $userId
                        : null,
                    $purchaseId
                        ? (int) $purchaseId
                        : null
                );

            return response()->json(
                $result
            );

        } catch (Throwable $e) {

            Log::error(
                'Purchase Webhook Controller Error',
                [
                    'invoice_id' => $request->input('invoice_id'),
                    'tx_hash' => $request->input('txHash'),
                    'user_id' => $request->query('user_id'),
                    'purchase_id' => $request->query('purchase_id'),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );

            return response()->json([
                'status' => false,

                'message' =>
                    'Something went wrong. Please try again later.',
            ], 500);
        }
    }
}
