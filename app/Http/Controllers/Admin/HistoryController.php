<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class HistoryController extends Controller
{
        public function purchase(Request $request)
    {
        $query = Purchase::query()
            ->with([
                'user:id,name,email,wallet_address'
            ]);

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                // Search User Information
                $q->whereHas('user', function ($userQuery) use ($search) {

                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('wallet_address', 'like', "%{$search}%");

                })

                // Purchase fields
                ->orWhere('invoice_id', 'like', "%{$search}%")
                ->orWhere('tx_hash', 'like', "%{$search}%");

            });
        }

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        $purchases = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.pages.purchases.index',
            compact('purchases')
        );
    }


    /**
     * Update Purchase Status
     */
    public function updatePurchaseStatus(
        Request $request,
        Purchase $purchase
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'completed',
                    'failed',
                    'cancelled',
                ]),
            ],

            'received_usdt' => [
                'nullable',
                'numeric',
                'gt:0',
            ],
        ]);

        $oldStatus = $purchase->status;
        $newStatus = $validated['status'];

        if ($oldStatus === 'completed') {
            throw ValidationException::withMessages([
                'status' => [
                    'Completed purchase status cannot be changed.'
                ],
            ]);
        }

        if (
            $oldStatus === 'pending' &&
            $newStatus === 'completed'
        ) {
            $request->validate([
                'received_usdt' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],
            ]);

            $receivedUsdt = number_format(
                (float) $validated['received_usdt'],
                8,
                '.',
                ''
            );

            $payableUsdt = number_format(
                (float) $purchase->payable_usdt,
                8,
                '.',
                ''
            );


            if (bccomp($receivedUsdt, $payableUsdt, 8) !== 0) {
                throw ValidationException::withMessages([
                    'received_usdt' => [
                        'Received USDT must exactly match the payable USDT amount of '
                        . number_format((float) $purchase->payable_usdt, 8)
                        . '.'
                    ],
                ]);
            }

            DB::transaction(function () use (
                $purchase,
                $receivedUsdt
            ) {

                $lockedPurchase = Purchase::query()
                    ->where('id', $purchase->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPurchase->status === 'completed') {
                    throw ValidationException::withMessages([
                        'status' => [
                            'This purchase has already been completed.'
                        ],
                    ]);
                }

                $payableUsdt = number_format(
                    (float) $lockedPurchase->payable_usdt,
                    8,
                    '.',
                    ''
                );

                if (bccomp($receivedUsdt, $payableUsdt, 8) !== 0) {
                    throw ValidationException::withMessages([
                        'received_usdt' => [
                            'Received USDT does not match the payable USDT amount.'
                        ],
                    ]);
                }

                $buyer = User::query()
                    ->where('id', $lockedPurchase->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();


                $mainMind = number_format(
                    (float) $lockedPurchase->mind_amount,
                    8,
                    '.',
                    ''
                );

                $bonusMind = number_format(
                    (float) $lockedPurchase->bonus_mind,
                    8,
                    '.',
                    ''
                );

                $totalMind = number_format(
                    (float) $lockedPurchase->total_mind,
                    8,
                    '.',
                    ''
                );

                $calculatedTotalMind = bcadd(
                    $mainMind,
                    $bonusMind,
                    8
                );

                if (bccomp($calculatedTotalMind, $totalMind, 8) !== 0) {
                    throw ValidationException::withMessages([
                        'status' => [
                            'Purchase MIND calculation is inconsistent. '
                            . 'Please verify the purchase data before completing it.'
                        ],
                    ]);
                }

                $currentBalance = number_format(
                    (float) $buyer->mind_balance,
                    8,
                    '.',
                    ''
                );

                $newBalance = bcadd(
                    $currentBalance,
                    $totalMind,
                    8
                );

                $buyer->mind_balance = $newBalance;
                $buyer->save();

                Transaction::create([
                    'user_id' => $buyer->id,
                    'purchase_id' => $lockedPurchase->id,
                    'type' => 'purchase',
                    'amount_mind' => $totalMind,
                    'amount_usdt' => $receivedUsdt,
                    'source_user_id' => null,
                    'rate_applied' => $lockedPurchase->mind_price,
                    'description' =>
                        'MIND purchase completed. '
                        . 'Main: ' . $mainMind
                        . ' MIND, Bonus: ' . $bonusMind
                        . ' MIND.',
                    'status' => 'completed',
                    'created_at' => now(),
                ]);

                $referrerId = $buyer->referred_id;

                if ($referrerId) {

                    $referralPercentage = Setting::query()
                        ->where('key', 'referral_commission')
                        ->value('value');


                    $referralPercentage = (float) (
                        $referralPercentage ?? 0
                    );

                    if ($referralPercentage <= 0) {

                        $setting = Setting::query()
                            ->where('key', 'referral_bonus_percentage')
                            ->first();

                        if ($setting) {
                            $referralPercentage = (float) $setting->value;
                        }
                    }

                    if ($referralPercentage > 0) {

                        $referralBonusMind = bcdiv(
                            bcmul(
                                $totalMind,
                                number_format(
                                    $referralPercentage,
                                    4,
                                    '.',
                                    ''
                                ),
                                8
                            ),
                            '100',
                            8
                        );

                        if (bccomp($referralBonusMind, '0', 8) > 0) {


                            $referrer = User::query()
                                ->where('id', $referrerId)
                                ->lockForUpdate()
                                ->first();

                            if ($referrer) {

                                $referrerBalance = number_format(
                                    (float) $referrer->mind_balance,
                                    8,
                                    '.',
                                    ''
                                );

                                $newReferrerBalance = bcadd(
                                    $referrerBalance,
                                    $referralBonusMind,
                                    8
                                );

                                $referrer->mind_balance =
                                    $newReferrerBalance;

                                $referrer->save();


                                $referralUsdtValue = bcmul(
                                    $referralBonusMind,
                                    (string) $lockedPurchase->mind_price,
                                    8
                                );

                                Transaction::create([
                                    'user_id' => $referrer->id,
                                    'purchase_id' => $lockedPurchase->id,
                                    'type' => 'referral_bonus',
                                    'amount_mind' => $referralBonusMind,
                                    'amount_usdt' => $referralUsdtValue,
                                    'source_user_id' => $buyer->id,
                                    'rate_applied' => $lockedPurchase->mind_price,
                                    'description' =>
                                        'Referral bonus from user '
                                        . $buyer->wallet_address
                                        . ' at '
                                        . $referralPercentage
                                        . '%.',
                                    'status' => 'completed',
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }
                }

                $lockedPurchase->received_usdt = $receivedUsdt;
                $lockedPurchase->status = 'completed';
                $lockedPurchase->paid_at =
                    $lockedPurchase->paid_at ?? now();
                $lockedPurchase->completed_at =
                    $lockedPurchase->completed_at ?? now();

                $lockedPurchase->save();
            });

            return back()->with(
                'success',
                'Purchase completed successfully. MIND balance and applicable bonuses have been credited.'
            );
        }


        $purchase->status = $newStatus;

        if ($newStatus === 'failed' || $newStatus === 'cancelled') {
            // Keep payment timestamps unchanged.
        }

        $purchase->save();

        return back()->with(
            'success',
            'Purchase status updated from '
            . ucfirst($oldStatus)
            . ' to '
            . ucfirst($newStatus)
            . '.'
        );
    }

    public function transactions(Request $request)
    {
        $query = Transaction::query()
            ->with([
                'user:id,name,email,wallet_address',
                'sourceUser:id,name,email,wallet_address',
            ]);


        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                // Order ID
                $q->where('order_id', 'like', "%{$search}%")

                    // User fields
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('wallet_address', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }


        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.pages.transactions.index',
            compact('transactions')
        );
    }
}
