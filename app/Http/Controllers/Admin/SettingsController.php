<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $settings = Setting::query()
            ->whereNotIn('group', ['payment','system',])
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');

        return view(
            'admin.pages.settings.index',
            compact('settings')
        );
    }


    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $settings = Setting::query()->get();

        DB::transaction(function () use ($settings, $request) {

            foreach ($settings as $setting) {

                $fieldName = 'settings.' . $setting->key;

                /*
                |--------------------------------------------------------------------------
                | File Uploads
                |--------------------------------------------------------------------------
                */
                if (in_array($setting->key, ['logo', 'favicon'], true)) {

                    if (!$request->hasFile($fieldName)) {
                        continue;
                    }

                    $file = $request->file($fieldName);

                    if (!$file || !$file->isValid()) {
                        continue;
                    }

                    if ($setting->key === 'logo') {

                        $request->validate([
                            $fieldName => [
                                'nullable',
                                'image',
                                'mimes:jpg,jpeg,png,webp,svg',
                                'max:2048',
                            ],
                        ]);

                        if (
                            $setting->value &&
                            Storage::disk('public')->exists($setting->value)
                        ) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        $value = $file->store('logos', 'public');

                    } else {

                        $request->validate([
                            $fieldName => [
                                'nullable',
                                'file',
                                'mimes:ico,png,jpg,jpeg,webp',
                                'max:1024',
                            ],
                        ]);

                        if (
                            $setting->value &&
                            Storage::disk('public')->exists($setting->value)
                        ) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        $value = $file->store('favicons', 'public');
                    }

                    $setting->value = $value;
                    $setting->save();

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Boolean
                |--------------------------------------------------------------------------
                */
                if ($setting->type === 'boolean') {

                    $value = $request->boolean($fieldName)
                        ? '1'
                        : '0';

                    $setting->value = $value;
                    $setting->save();

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                | If field is not present in the form, don't update it.
                |--------------------------------------------------------------------------
                */
                if (!$request->has($fieldName)) {
                    continue;
                }


                $value = $request->input($fieldName);


                /*
                |--------------------------------------------------------------------------
                | String
                |--------------------------------------------------------------------------
                */
                if ($setting->type === 'string') {

                    if (is_array($value)) {
                        throw ValidationException::withMessages([
                            $fieldName => ['Invalid value.'],
                        ]);
                    }

                    $value = trim((string) $value);
                }


                /*
                |--------------------------------------------------------------------------
                | Decimal
                |--------------------------------------------------------------------------
                */
                elseif ($setting->type === 'decimal') {

                    if (
                        $value === null ||
                        $value === '' ||
                        !is_numeric($value)
                    ) {
                        throw ValidationException::withMessages([
                            $fieldName => [
                                $setting->key . ' must be a valid number.'
                            ],
                        ]);
                    }

                    $value = number_format(
                        (float) $value,
                        8,
                        '.',
                        ''
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Integer
                |--------------------------------------------------------------------------
                */
                elseif ($setting->type === 'integer') {

                    if (
                        $value === null ||
                        $value === '' ||
                        filter_var($value, FILTER_VALIDATE_INT) === false
                    ) {
                        throw ValidationException::withMessages([
                            $fieldName => [
                                $setting->key . ' must be a valid integer.'
                            ],
                        ]);
                    }

                    $value = (string) (int) $value;
                }


                /*
                |--------------------------------------------------------------------------
                | JSON
                |--------------------------------------------------------------------------
                */
                elseif ($setting->type === 'json') {

                    if (is_array($value)) {

                        $value = json_encode(
                            $value,
                            JSON_UNESCAPED_SLASHES |
                            JSON_UNESCAPED_UNICODE
                        );

                    } else {

                        $value = trim((string) $value);

                        json_decode($value, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {

                            throw ValidationException::withMessages([
                                $fieldName => [
                                    'Invalid JSON format: ' .
                                    json_last_error_msg()
                                ],
                            ]);
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */
                $setting->value = $value;
                $setting->save();
            }
        });

        return redirect()
            ->route('admin.settings.index')
            ->with(
                'success',
                'Settings updated successfully.'
            );
    }
}
