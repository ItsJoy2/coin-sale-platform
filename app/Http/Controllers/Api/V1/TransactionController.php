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

            $transactionQuery = Transaction::query()
                ->where('user_id', $user->id)
                ->with([
                    'purchase:id,invoice_id,tx_hash,payment_address,status,paid_at,completed_at',
                    'sourceUser:id,user_name,wallet_address',
                ])
                ->orderByDesc('id');

            if ($type) {
                $transactionQuery->where('type', $type);
            }

            if ($status) {
                $transactionQuery->where('status', $status);
            }

            $transactions = $transactionQuery->get();

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
                ];
            });

            $pendingPurchaseData = collect();

            if (!$type || $type === 'purchase') {

                $pendingPurchaseQuery = Purchase::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->orderByDesc('id');

                if ($status) {
                    $pendingPurchaseQuery->where('status', $status);
                }

                $pendingPurchases = $pendingPurchaseQuery->get();

                $pendingPurchaseData = $pendingPurchases->map(function ($purchase) {

                    return [
                        'id' => $purchase->id,
                        'order_id' => $purchase->invoice_id,
                        'type' => 'purchase',
                        'amount_mind' => $purchase->amount_mind ?? null,
                        'amount_usdt' => $purchase->amount_usdt ?? null,
                        'rate_applied' => $purchase->rate_applied ?? null,
                        'description' => 'Purchase payment is pending.',
                        'status' => 'pending',
                        'created_at' => $purchase->created_at,
                    ];
                });
            }

            $data = $transactionData
                ->concat($pendingPurchaseData)
                ->sortByDesc(function ($item) {

                    return $item['created_at']
                        ? $item['created_at']->timestamp
                        : 0;
                })
                ->values();


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
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch transaction history.',
            ], 500);
        }
    }

}
