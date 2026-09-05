<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Models\Purchase;
use App\Models\Transaction;
use Illuminate\Http\Request;
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

    public function profile(Request $request)
    {
        try {

            $user = $request->user();

            $totalUsdBalance = Purchase::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('received_usdt');

            $referralBonusMind = Transaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'referral_bonus')
                ->sum('amount_mind');


            return response()->json([
                'status' => true,
                'message' => 'Profile retrieved successfully.',
                'data' => [
                    'user' => $user,

                    'total_usd_balance' => (float) $totalUsdBalance,

                    'referral_bonus' => [
                        'mind' => (float) $referralBonusMind,
                    ],
                ],
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }
}
