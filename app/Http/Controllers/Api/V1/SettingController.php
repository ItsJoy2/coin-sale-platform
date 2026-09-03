<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        try {

            $publicSettings = [
                'general' => [
                    'site_name',
                    'logo',
                    'favicon',
                ],

                'mind' => [
                    'mind_price',
                ],

                'purchase' => [
                    'purchase_slots',
                ],
            ];

            $keys = collect($publicSettings)
                ->flatten()
                ->unique()
                ->values()
                ->all();

            $settings = Setting::query()
                ->where('is_public', true)
                ->whereIn('key', $keys)
                ->get()
                ->keyBy('key');

            $data = [];

            foreach ($publicSettings as $group => $groupKeys) {

                foreach ($groupKeys as $key) {

                    if (!$settings->has($key)) {
                        continue;
                    }

                    $data[$group][$key] =
                        $settings[$key]->typed_value;
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Settings retrieved successfully.',
                'data' => $data,
            ]);

        } catch (Throwable $e) {

            Log::error('Public Settings API Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again later.',
                'data' => null,
            ], 500);
        }
    }
}
