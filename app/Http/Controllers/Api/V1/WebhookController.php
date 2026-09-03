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

    public function handle(Request $request)
    {
        try {

            $invoiceId = $request->input('invoice_id');
            $txHash = $request->input('txHash');

            // user_id URL query থেকে আসবে
            $userId = $request->query('user_id');

            if (!$invoiceId || !$txHash) {
                return response()->json([
                    'status' => false,
                    'message' => 'invoice_id and txHash are required.',
                ], 400);
            }

            $result = $this->purchaseService->processWebhook(
                invoiceId: $invoiceId,
                txHash: $txHash,
                userId: $userId ? (int) $userId : null
            );

            return response()->json($result);

        } catch (Throwable $e) {

            Log::error('Purchase Webhook Error', [
                'invoice_id' => $request->input('invoice_id'),
                'tx_hash' => $request->input('txHash'),
                'user_id' => $request->query('user_id'),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }
}
