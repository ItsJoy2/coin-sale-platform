<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthService
{
    /**
     * Register User
     */
    public function register(array $data): array
    {
        try {

            return DB::transaction(function () use ($data) {

                $referrer = null;

                if (!empty($data['referral_code'])) {
                    $referrer = User::where(
                        'referral_code',
                        $data['referral_code']
                    )->first();
                }

                do {
                    $referralCode = strtoupper(
                        Str::random(8)
                    );
                } while (
                    User::where(
                        'referral_code',
                        $referralCode
                    )->exists()
                );

                $user = User::create([
                    'wallet_address' => $data['wallet_address'],
                    'password' => Hash::make($data['password']),
                    'referral_code' => $referralCode,
                    'referred_id' => $referrer?->id,
                    'role' => 'user',
                    'mind_balance' => 0,
                ]);

                $token = $user->createToken(
                    'user-api'
                )->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                ];
            });

        } catch (Throwable $e) {

            Log::error('AuthService::register failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'wallet_address' => $data['wallet_address'] ?? null,
            ]);

            throw new \RuntimeException(
                'Something went wrong. Please try again later.'
            );
        }
    }


    /**
     * Login User
     */
    public function login(array $data): array
    {
        try {

            $user = User::where(
                'wallet_address',
                $data['wallet_address']
            )->first();

            /*
             * Don't reveal whether the wallet exists.
             */
            if (
                !$user ||
                !Hash::check(
                    $data['password'],
                    $user->password
                )
            ) {
                throw ValidationException::withMessages([
                    'wallet_address' => [
                        'Invalid wallet address or password.'
                    ],
                ]);
            }

            /*
             * Admin cannot login through user authentication.
             */
            if ($user->role !== 'user') {
                throw ValidationException::withMessages([
                    'wallet_address' => [
                        'Invalid wallet address or password.'
                    ],
                ]);
            }

            /*
             * Revoke previous tokens.
             */
            $user->tokens()->delete();

            $token = $user->createToken(
                'user-api'
            )->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error('AuthService::login failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'wallet_address' => $data['wallet_address'] ?? null,
            ]);

            throw new \RuntimeException(
                'Something went wrong. Please try again later.'
            );
        }
    }


    /**
     * Logout User
     */
    public function logout(User $user): void
    {
        try {

            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }

        } catch (Throwable $e) {

            Log::error('AuthService::logout failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $user->id,
            ]);

            throw new \RuntimeException(
                'Something went wrong. Please try again later.'
            );
        }
    }


    /**
     * Forgot Password
     */
    public function forgotPassword(string $email): bool
    {
        try {

            $status = Password::sendResetLink([
                'email' => $email,
            ]);

            return $status === Password::RESET_LINK_SENT;

        } catch (Throwable $e) {

            Log::error('AuthService::forgotPassword failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $email,
            ]);

            throw new \RuntimeException(
                'Something went wrong. Please try again later.'
            );
        }
    }
}
