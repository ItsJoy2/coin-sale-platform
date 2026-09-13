<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {

            $totalUsers = User::Where('role', 'user')->count();

            $purchaseQuery = Purchase::query()
                ->where('status', 'completed');

            $totalPurchases = (clone $purchaseQuery)->count();

            $totalUsdtSold = (clone $purchaseQuery)
                ->sum('received_usdt');

            $totalMindSold = (clone $purchaseQuery)
                ->sum('total_mind');

            $totalMainMind = (clone $purchaseQuery)
                ->sum('mind_amount');

            $totalBonusMind = (clone $purchaseQuery)
                ->sum('bonus_mind');

            $totalBuyers = (clone $purchaseQuery)
                ->distinct('user_id')
                ->count('user_id');


            $averagePurchaseUsdt = $totalPurchases > 0
                ? $totalUsdtSold / $totalPurchases
                : 0;

            $averageMindPerPurchase = $totalPurchases > 0
                ? $totalMindSold / $totalPurchases
                : 0;

            $pendingPurchases = Purchase::where(
                'status',
                'pending'
            )->count();

            $referralQuery = Transaction::query()
                ->where('type', 'referral_bonus')
                ->where('status', 'completed');

            $totalReferralBonusMind = $referralQuery
                ->sum('amount_mind');

            $totalReferralBonusUsdt = $referralQuery
                ->sum('amount_usdt');


            $todayQuery = Purchase::query()
                ->where('status', 'completed')
                ->whereDate('completed_at', today());

            $todayPurchases = (clone $todayQuery)->count();

            $todayUsdt = (clone $todayQuery)
                ->sum('received_usdt');

            $todayMind = (clone $todayQuery)
                ->sum('total_mind');


            $monthQuery = Purchase::query()
                ->where('status', 'completed')
                ->whereYear('completed_at', now()->year)
                ->whereMonth('completed_at', now()->month);

            $monthPurchases = (clone $monthQuery)->count();

            $monthUsdt = (clone $monthQuery)
                ->sum('received_usdt');

            $monthMind = (clone $monthQuery)
                ->sum('total_mind');

            $latestPurchases = Purchase::query()
                ->where('status', 'completed')
                ->with([
                    'user:id,name,wallet_address',
                ])
                ->orderByDesc('completed_at')
                ->limit(10)
                ->get([
                    'id',
                    'user_id',
                    'invoice_id',
                    'received_usdt',
                    'mind_amount',
                    'bonus_mind',
                    'total_mind',
                    'mind_price',
                    'tx_hash',
                    'status',
                    'completed_at',
                ]);


            $DashboardData = [

                'totalUsers' => $totalUsers,
                // 'activeUsers' => $activeUsers,
                // 'inactiveUsers' => $inactiveUsers,
                // 'blockedUsers' => $blockedUsers,

                'sales' => [

                    'total_purchases' => $totalPurchases,

                    'total_buyers' => $totalBuyers,

                    'total_usdt_sold' => $totalUsdtSold,

                    'total_mind_sold' => $totalMindSold,

                    'total_main_mind' => $totalMainMind,

                    'total_bonus_mind' => $totalBonusMind,

                    'average_purchase_usdt' => $averagePurchaseUsdt,

                    'average_mind_per_purchase' => $averageMindPerPurchase,

                ],

                'pending' => [

                    'pending_purchases' => $pendingPurchases,

                ],


                'referral' => [

                    'total_referral_bonus_mind' => $totalReferralBonusMind,

                    'total_referral_bonus_usdt' => $totalReferralBonusUsdt,

                ],

                'today' => [

                    'purchases' => $todayPurchases,

                    'usdt' => $todayUsdt,

                    'mind' => $todayMind,

                ],

                'this_month' => [

                    'purchases' => $monthPurchases,

                    'usdt' => $monthUsdt,

                    'mind' => $monthMind,

                ],


                'latest_purchases' => $latestPurchases,

            ];


            return view('admin.dashboard', compact('DashboardData'));

            } catch (\Throwable $e) {

                Log::error('Admin dashboard error', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], 500);
            }
    }
}
