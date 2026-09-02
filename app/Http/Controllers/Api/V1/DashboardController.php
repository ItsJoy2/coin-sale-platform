<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BonusTier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Something went wrong. Please try again later.',
                ], 401);
            }

            $totalUsdSpent = Transaction::where(
                'user_id',
                $user->id
            )
                ->where('type', 'purchase')
                ->sum('amount_usdt');

            $totalPurchaseBonus = Transaction::where(
                'user_id',
                $user->id
            )
                ->whereIn('type', [
                    'tier_bonus',
                    'coupon_bonus',
                ])
                ->sum('amount_mind');

            $totalReferralBonus = Transaction::where(
                'user_id',
                $user->id
            )
                ->where('type', 'referral_bonus')
                ->sum('amount_usdt');

            $totalReferrals =
                $user->referrals()->count();

            $activeBonusTiers = BonusTier::where(
                'is_active',
                true
            )
                ->orderBy('min_amount_usd')
                ->get();

            return response()->json([
                'status' => true,
                'message' =>
                    'Dashboard data retrieved successfully.',
                'data' => [
                    'mind_balance' =>$user->mind_balance,
                    'total_usd_spent' =>$totalUsdSpent,
                    'total_purchase_bonus_mind' =>$totalPurchaseBonus,
                    'total_referral_bonus_usdt' =>$totalReferralBonus,
                    'total_referrals_count' =>$totalReferrals,
                    'current_mind_price' =>0.41,
                    'active_bonus_tiers' =>$activeBonusTiers,
                ],
            ]);

        } catch (Throwable $e) {

            Log::error('Dashboard Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' =>
                    $request->user()?->id,
            ]);

            return response()->json([
                'status' => false,
                'message' =>
                    'Something went wrong. Please try again later.',
            ], 500);
        }
    }
}
