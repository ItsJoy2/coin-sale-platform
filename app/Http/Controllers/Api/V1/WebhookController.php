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
        Log::info('===== PURCHASE WEBHOOK RECEIVED =====', [
            'method'  => $request->method(),
            'url'     => $request->fullUrl(),
            'query'   => $request->query(),
            'body'    => $request->all(),
            'headers' => [
                'content_type' => $request->header('Content-Type'),
                'user_agent'   => $request->userAgent(),
            ],
        ]);

        try {

            $invoiceId = trim(
                (string) $request->input('invoice_id')
            );

            $txHash = trim(
                (string) $request->input('txHash')
            );

            $userId = $request->query('user_id');

            if (!$invoiceId) {

                Log::error(
                    'Webhook invoice_id missing.',
                    [
                        'body' => $request->all(),
                    ]
                );

                return response()->json([
                    'status'  => false,
                    'message' => 'invoice_id is required.',
                ], 400);
            }

            if (!$txHash) {

                Log::error(
                    'Webhook txHash missing.',
                    [
                        'invoice_id' => $invoiceId,
                        'body'       => $request->all(),
                    ]
                );

                return response()->json([
                    'status'  => false,
                    'message' => 'txHash is required.',
                ], 400);
            }

            if (!$userId || !is_numeric($userId)) {

                Log::error(
                    'Webhook user_id missing or invalid.',
                    [
                        'invoice_id' => $invoiceId,
                        'tx_hash'    => $txHash,
                        'user_id'    => $userId,
                    ]
                );

                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid user_id.',
                ], 400);
            }

            $result = $this->purchaseService->processWebhook(
                invoiceId: $invoiceId,
                txHash: $txHash,
                userId: (int) $userId
            );

            Log::info(
                '===== PURCHASE WEBHOOK COMPLETED =====',
                [
                    'invoice_id' => $invoiceId,
                    'tx_hash'    => $txHash,
                    'user_id'    => $userId,
                    'result'     => $result,
                ]
            );

            return response()->json($result);

        } catch (Throwable $e) {

            Log::error(
                '===== PURCHASE WEBHOOK ERROR =====',
                [
                    'invoice_id' => $request->input('invoice_id'),
                    'tx_hash'    => $request->input('txHash'),
                    'user_id'    => $request->query('user_id'),
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => $e->getTraceAsString(),
                ]
            );

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
