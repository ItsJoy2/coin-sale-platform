<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show Profile
     */
    public function show(Request $request)
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

            return response()->json([
                'status' => true,
                'message' =>
                    'Profile retrieved successfully.',
                'data' => [
                    'id' => $user->id,
                    'wallet_address' =>
                        $user->wallet_address,
                    'email' => $user->email,
                    'name' => $user->name,
                    'address' => $user->address,
                    'referral_code' =>
                        $user->referral_code,
                    'mind_balance' =>
                        $user->mind_balance,
                    'referrals_count' =>
                        $user->referrals()->count(),
                ],
            ]);

        } catch (Throwable $e) {

            Log::error('Profile Show Error', [
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


    /**
     * Update Profile
     */
    public function update(Request $request)
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

            $validated = $request->validate([
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('users', 'email')
                        ->ignore($user->id),
                ],

                'name' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'address' => [
                    'nullable',
                    'string',
                ],
            ]);

            $user->update($validated);

            return response()->json([
                'status' => true,
                'message' =>
                    'Profile updated successfully.',
                'data' => $user->fresh(),
            ]);

        } catch (ValidationException $e) {

            throw $e;

        } catch (Throwable $e) {

            Log::error('Profile Update Error', [
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
