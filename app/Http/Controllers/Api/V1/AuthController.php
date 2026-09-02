<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
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
                'referral_code' => ['nullable', 'string', 'exists:users, referral_code']
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
}
