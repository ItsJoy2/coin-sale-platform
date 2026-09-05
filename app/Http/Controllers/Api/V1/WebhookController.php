<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    // public function handle(Request $request)
    // {
    //     Log::emergency('===== PURCHASE WEBHOOK RECEIVED =====', [
    //         'method'   => $request->method(),
    //         'url'      => $request->fullUrl(),
    //         'user_id'  => $request->query('user_id'),
    //         'txHash'   => $request->input('txHash'),
    //         'tx_hash'  => $request->input('tx_hash'),
    //         'query'    => $request->query(),
    //         'body'     => $request->all(),
    //         'headers'  => $request->headers->all(),
    //         'ip'       => $request->ip(),
    //     ]);

    //     try {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | 1. Get user_id from webhook URL
    //         |--------------------------------------------------------------------------
    //         */

    //         $userId = $request->query('user_id');

    //         if ($userId === null || $userId === '') {

    //             Log::error('Webhook user_id missing', [
    //                 'url' => $request->fullUrl(),
    //             ]);

    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'user_id is required.',
    //             ], 400);
    //         }

    //         $userId = (int) $userId;

    //         if ($userId <= 0) {

    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'Invalid user_id.',
    //             ], 400);
    //         }


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 2. Get TX HASH
    //         |--------------------------------------------------------------------------
    //         */

    //         $txHash = $request->input('txHash');

    //         if (!$txHash) {
    //             $txHash = $request->input('tx_hash');
    //         }

    //         /*
    //          * Sometimes gateway may send txHash through query string.
    //          */
    //         if (!$txHash) {
    //             $txHash = $request->query('txHash');
    //         }

    //         if (!$txHash) {
    //             $txHash = $request->query('tx_hash');
    //         }

    //         /*
    //          * Raw JSON fallback
    //          */
    //         if (!$txHash) {

    //             $rawPayload = json_decode(
    //                 $request->getContent(),
    //                 true
    //             );

    //             if (is_array($rawPayload)) {
    //                 $txHash =
    //                     $rawPayload['txHash']
    //                     ?? $rawPayload['tx_hash']
    //                     ?? null;
    //             }
    //         }

    //         $txHash = trim((string) $txHash);

    //         if ($txHash === '') {

    //             Log::error('Webhook TX hash missing', [
    //                 'user_id' => $userId,
    //                 'body'    => $request->all(),
    //             ]);

    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'txHash is required.',
    //             ], 400);
    //         }


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 3. VERY IMPORTANT
    //         |
    //         | Find purchase and SAVE TX HASH FIRST.
    //         |
    //         | This transaction is intentionally separate from payment processing.
    //         | So even if gateway API fails, txHash remains saved in DB.
    //         |--------------------------------------------------------------------------
    //         */

    //         $purchase = DB::transaction(function () use (
    //             $userId,
    //             $txHash
    //         ) {

    //             /*
    //              * First try existing tx hash.
    //              */
    //             $purchase = Purchase::query()
    //                 ->where('user_id', $userId)
    //                 ->where('tx_hash', $txHash)
    //                 ->whereIn('status', [
    //                     'pending',
    //                     'processing',
    //                     'completed',
    //                 ])
    //                 ->lockForUpdate()
    //                 ->first();

    //             /*
    //              * If this TX is not already attached,
    //              * take oldest pending/processing purchase.
    //              */
    //             if (!$purchase) {

    //                 $purchase = Purchase::query()
    //                     ->where('user_id', $userId)
    //                     ->whereIn('status', [
    //                         'pending',
    //                         'processing',
    //                     ])
    //                     ->where(function ($query) {

    //                         $query
    //                             ->whereNull('tx_hash')
    //                             ->orWhere('tx_hash', '');

    //                     })
    //                     ->orderBy('id', 'asc')
    //                     ->lockForUpdate()
    //                     ->first();
    //             }

    //             if (!$purchase) {
    //                 return null;
    //             }

    //             /*
    //              * SAVE TX HASH IMMEDIATELY
    //              */
    //             $purchase->tx_hash = $txHash;

    //             /*
    //              * Keep pending/processing state.
    //              * Do not complete here.
    //              */
    //             if ($purchase->status === 'pending') {
    //                 $purchase->status = 'processing';
    //             }

    //             $purchase->save();

    //             return $purchase;
    //         });


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 4. No purchase found
    //         |--------------------------------------------------------------------------
    //         */

    //         if (!$purchase) {

    //             Log::warning(
    //                 'Webhook received but no pending purchase found',
    //                 [
    //                     'user_id' => $userId,
    //                     'tx_hash' => $txHash,
    //                 ]
    //             );

    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'No pending purchase found for this user.',
    //                 'data'    => [
    //                     'user_id' => $userId,
    //                     'txHash'  => $txHash,
    //                 ],
    //             ], 404);
    //         }


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 5. TX HASH IS NOW ALREADY SAVED
    //         |--------------------------------------------------------------------------
    //         */

    //         Log::info(
    //             'Webhook TX hash saved successfully',
    //             [
    //                 'purchase_id' => $purchase->id,
    //                 'user_id'     => $userId,
    //                 'tx_hash'     => $txHash,
    //             ]
    //         );


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 6. Now process payment
    //         |--------------------------------------------------------------------------
    //         */

    //         $result = $this->purchaseService->processWebhook(
    //             txHash: $txHash,
    //             userId: $userId
    //         );


    //         /*
    //         |--------------------------------------------------------------------------
    //         | 7. Return gateway/payment processing result
    //         |--------------------------------------------------------------------------
    //         */

    //         return response()->json($result);

    //     } catch (Throwable $e) {

    //         Log::error('===== PURCHASE WEBHOOK ERROR =====', [
    //             'user_id' => $request->query('user_id'),
    //             'tx_hash' => $request->input('txHash')
    //                 ?? $request->input('tx_hash'),
    //             'message' => $e->getMessage(),
    //             'file'    => $e->getFile(),
    //             'line'    => $e->getLine(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Webhook processing failed.',
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

        public function handle(Request $request)
    {
        Log::emergency('WEBHOOK HIT');

        file_put_contents(
            storage_path('logs/webhook-test.log'),
            date('Y-m-d H:i:s') . PHP_EOL .
            'URL: ' . $request->fullUrl() . PHP_EOL .
            'METHOD: ' . $request->method() . PHP_EOL .
            'QUERY: ' . json_encode($request->query()) . PHP_EOL .
            'BODY: ' . $request->getContent() . PHP_EOL .
            '------------------------' . PHP_EOL,
            FILE_APPEND
        );

        return response()->json([
            'status' => true,
            'message' => 'WEBHOOK RECEIVED',
            'data' => [
                'user_id' => $request->query('user_id'),
                'txHash' => $request->input('txHash'),
                'body' => $request->all(),
            ],
        ]);
    }
}
