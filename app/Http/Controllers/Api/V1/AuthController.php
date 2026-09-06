<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    /**
     * Register
     */
    public function register(Request $request)
    {
        try {

            $validated = $request->validate([
                'wallet_address' => ['required','string','regex:/^0x[a-fA-F0-9]{40}$/','unique:users,wallet_address',],
                'password' => ['required','string','min:8','confirmed'],
                'referral_code' => ['nullable', 'string', 'exists:users,referral_code']
            ]);

            $result = $this->authService->register($validated);

            return response()->json([
                'status' => true,
                'message' => 'Registration successful.',
                'data' => $result,
            ], 201);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    /**
     * Login
     */
    public function login(Request $request)
    {
        try {

            $validated = $request->validate([
                'wallet_address' => ['required', 'string','regex:/^0x[a-fA-F0-9]{40}$/'],
                'password' => ['required','string'],
            ]);

            $result = $this->authService->login($validated);

            return response()->json([
                'status' => true,
                'message' => 'Login successful.',
                'data' => $result,
            ]);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {

            $this->authService->logout(
                $request->user()
            );

            return response()->json([
                'status' => true,
                'message' => 'Logout successful.',
            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }


    /**
     * Forgot Password
     */
    public function forgotPassword(Request $request)
    {
        try {

            $validated = $request->validate([
                'email' => ['required','email'],
            ]);

            $result = $this->authService->forgotPassword(
                $validated['email']
            );

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Something went wrong. Please try again later.'
                ], 422);
            }

            return response()->json([
                'status' => true,
                'message' => 'Password reset link sent successfully.'
            ]);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }


    public function changePassword(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $validated = $request->validate([
                'current_password' => ['required','string',],
                'password' => ['required','string','min:8','confirmed',],
            ]);

            if (!Hash::check(
                $validated['current_password'],
                $user->password
            )) {
                return response()->json([
                    'status' => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            $user->password = Hash::make(
                $validated['password']
            );

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully.',
            ], 200);

        } catch (ValidationException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {

            Log::error('Change password error', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to change password.',
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | Total USD Balance
            |--------------------------------------------------------------------------
            */

            $totalUsdBalance = Purchase::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('received_usdt');


            /*
            |--------------------------------------------------------------------------
            | Total Referral Bonus
            |--------------------------------------------------------------------------
            */

            $referralBonusMind = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'referral_bonus')
                ->sum('amount_mind');


            /*
            |--------------------------------------------------------------------------
            | Get Referral Users
            |--------------------------------------------------------------------------
            */

            $referralUsers = User::query()
                ->where('referred_id', $user->id)
                ->select(['id','wallet_address', 'name', 'created_at',])
                ->orderByDesc('id')
                ->get();

            $referralBonusTransactions = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'referral_bonus')
                ->whereIn('source_user_id',$referralUsers->pluck('id'))
                ->select(['id','source_user_id','amount_mind','rate_applied','created_at',])
                ->orderByDesc('id')
                ->get()
                ->groupBy('source_user_id');


            $userData = $user->toArray();

            unset($userData['role']);



            $referralUserData = $referralUsers->map(
                function ($referralUser) use ($referralBonusTransactions) {

                    $transactions = $referralBonusTransactions->get(
                        $referralUser->id,
                        collect()
                    );

                    $totalMindBonus = $transactions->sum(
                        function ($transaction) {
                            return (float) $transaction->amount_mind;
                        }
                    );

                    $totalUsdtValue = $transactions->sum(
                        function ($transaction) {

                            $amountMind = (float) $transaction->amount_mind;

                            $rateApplied = (float) $transaction->rate_applied;

                            return $amountMind * $rateApplied;
                        }
                    );

                    $bonusTransactions = $transactions->map(
                        function ($transaction) {

                            $amountMind = (float) $transaction->amount_mind;

                            $rateApplied = (float) $transaction->rate_applied;

                            $amountUsdt = $amountMind * $rateApplied;

                            return [
                                'id' => $transaction->id,
                                'amount_mind' => round($amountMind, 8 ),
                                'rate_applied' => round($rateApplied, 8 ),
                                'amount_usdt' => round( $amountUsdt, 8),
                                'created_at' => $transaction->created_at,
                            ];
                        }
                    )->values();

                    return [
                        'id' => $referralUser->id,
                        'name' => $referralUser->name,
                        'wallet_address' => $referralUser->wallet_address,

                        'referral_bonus' => [
                            'mind' => round($totalMindBonus, 8 ),
                            'usdt' => round($totalUsdtValue, 8), ],

                        'created_at' => $referralUser->created_at,
                    ];
                }
            )->values();


            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => true,

                'message' => 'Profile retrieved successfully.',

                'data' => [
                    'user' => [

                        ...$userData,

                        'total_usd_balance' => round(
                            (float) $totalUsdBalance,
                            8
                        ),

                        'referral_bonus' => [
                            'mind' => round(
                                (float) $referralBonusMind,
                                8
                            ),
                        ],

                        'total_referral' => $referralUsers->count(),

                        'referral_users' => $referralUserData,
                    ],
                ],
            ], 200);


        } catch (\Throwable $e) {

            Log::error('Profile API Error', [
                'user_id' => $request->user()?->id,

                'message' => $e->getMessage(),

                'file' => $e->getFile(),

                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,

                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {

            $user = $request->user();

            $validated = $request->validate([
                'name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'address' => ['sometimes', 'nullable', 'string', 'max:500'],
                'email' => ['sometimes','nullable','max:255','unique:users,email,' . $user->id,
                ],
            ]);

            $user->update($validated);

            // Refresh user data
            $user->refresh();

            $userData = $user->toArray();
            unset($userData['role']);

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'user' => $userData,
                ],
            ], 200);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
}
