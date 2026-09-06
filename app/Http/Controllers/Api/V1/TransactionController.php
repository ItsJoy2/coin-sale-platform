<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
public function history(Request $request)
{
    try {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $type = $request->input('type');
        $status = $request->input('status');

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        $transactionQuery = Transaction::query()
            ->where('user_id', $user->id)
            ->with([
                'purchase:id,invoice_id,tx_hash,payment_address,status,paid_at,completed_at',
                'sourceUser:id,name,wallet_address',
            ])
            ->orderByDesc('id');

        /*
        |--------------------------------------------------------------------------
        | Type Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($type)) {
            $transactionQuery->where('type', $type);
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($status)) {
            $transactionQuery->where('status', $status);
        }

        $transactions = $transactionQuery->get();

        /*
        |--------------------------------------------------------------------------
        | Format Transactions
        |--------------------------------------------------------------------------
        */

        $transactionData = $transactions->map(function ($transaction) {

            return [
                'id' => $transaction->id,

                'order_id' => $transaction->order_id,

                'type' => $transaction->type,

                'amount_mind' => $transaction->amount_mind,

                'amount_usdt' => $transaction->amount_usdt,

                'rate_applied' => $transaction->rate_applied,

                'description' => $transaction->description,

                'status' => $transaction->status,

                'created_at' => $transaction->created_at,

                /*
                |--------------------------------------------------------------------------
                | Purchase Information
                |--------------------------------------------------------------------------
                */

                'purchase' => $transaction->purchase
                    ? [
                        'id' => $transaction->purchase->id,
                        'invoice_id' => $transaction->purchase->invoice_id,
                        'tx_hash' => $transaction->purchase->tx_hash,
                        'payment_address' => $transaction->purchase->payment_address,
                        'status' => $transaction->purchase->status,
                        'paid_at' => $transaction->purchase->paid_at,
                        'completed_at' => $transaction->purchase->completed_at,
                    ]
                    : null,

                /*
                |--------------------------------------------------------------------------
                | Source User
                |--------------------------------------------------------------------------
                */

                'source_user' => $transaction->sourceUser
                    ? [
                        'id' => $transaction->sourceUser->id,
                        'name' => $transaction->sourceUser->name,
                        'wallet_address' => $transaction->sourceUser->wallet_address,
                    ]
                    : null,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Pending Purchases
        |--------------------------------------------------------------------------
        |
        | Pending purchases are shown as "purchase" transactions only when:
        |
        | 1. No type filter is provided
        | 2. type=purchase
        |
        | And they are shown only when:
        |
        | 1. No status filter is provided
        | 2. status=pending
        |
        */

        $pendingPurchaseData = collect();

        if (!$type || $type === 'purchase') {

            if (!$status || $status === 'pending') {

                $pendingPurchases = Purchase::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->orderByDesc('id')
                    ->get();

                $pendingPurchaseData = $pendingPurchases->map(
                    function ($purchase) {

                        return [
                            'id' => $purchase->id,

                            'order_id' => $purchase->invoice_id,

                            'type' => 'purchase',

                            'amount_mind' => $purchase->mind_amount,

                            'amount_usdt' => $purchase->payable_usdt,

                            'rate_applied' => $purchase->mind_price,

                            'description' => 'Purchase payment is pending.',

                            'status' => 'pending',

                            'created_at' => $purchase->created_at,

                            'purchase' => [
                                'id' => $purchase->id,
                                'invoice_id' => $purchase->invoice_id,
                                'tx_hash' => $purchase->tx_hash,
                                'payment_address' => $purchase->payment_address,
                                'status' => $purchase->status,
                                'paid_at' => $purchase->paid_at,
                                'completed_at' => $purchase->completed_at,
                            ],

                            'source_user' => null,
                        ];
                    }
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Merge Transactions + Pending Purchases
        |--------------------------------------------------------------------------
        */

        $data = $transactionData
            ->concat($pendingPurchaseData)
            ->sortByDesc(function ($item) {

                if (!$item['created_at']) {
                    return 0;
                }

                return $item['created_at']->timestamp;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = 20;

        $currentPage = max(
            1,
            (int) $request->input('page', 1)
        );

        $total = $data->count();

        $items = $data
            ->slice(
                ($currentPage - 1) * $perPage,
                $perPage
            )
            ->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => true,

            'message' => 'Transaction history fetched successfully.',

            'data' => $paginator->items(),

            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);

    } catch (\Throwable $e) {

        Log::error('Transaction history error', [
            'user_id' => $request->user()?->id,
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'Unable to fetch transaction history.',
        ], 500);
    }
}

}
