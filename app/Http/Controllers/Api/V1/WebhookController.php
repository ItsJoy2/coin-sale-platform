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

    Log::emergency('===== PURCHASE WEBHOOK TEST RECEIVED =====', [
    'method' => $request->method(),
    'url' => $request->fullUrl(),
    'user_id' => $request->query('user_id'),
    'txHash' => $request->input('txHash'),
    'query' => $request->query(),
    'body' => $request->all(),
    'headers' => $request->headers->all(),
    'ip' => $request->ip(),
]);
        $txHash = trim((string) $request->input('txHash'));
        $userId = $request->query('user_id');

        try {

            if ($userId === null || $userId === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'user_id is required.',
                ], 400);
            }

            if ($txHash === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'txHash is required.',
                ], 400);
            }

            $result = $this->purchaseService->processWebhook(
                userId: (int) $userId,
                txHash: $txHash
            );

            return response()->json($result);

        } catch (Throwable $e) {

            Log::error('Purchase Webhook Error', [
                'user_id' => $userId,
                'tx_hash' => $txHash,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
