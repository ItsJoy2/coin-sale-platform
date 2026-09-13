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


        $request->validate([
            'settings.logo' => ['nullable','image','mimes:png,ico','max:2048', ],
            'settings.favicon' => ['nullable','file','mimes:png,ico', 'max:1024',],
        ]);


        $settings = Setting::query()->get();


        DB::transaction(function () use ($settings, $request) {

            foreach ($settings as $setting) {


                $key = $setting->key;


                if ($key === 'logo') {

                    if ($request->hasFile('settings.logo')) {

                        $file = $request->file('settings.logo');

                        if (
                            !empty($setting->value) &&
                            Storage::disk('public')->exists($setting->value)
                        ) {
                            Storage::disk('public')->delete(
                                $setting->value
                            );
                        }

                        $path = $file->store(
                            'logos',
                            'public'
                        );


                        $setting->value = $path;
                        $setting->save();
                    }


                    continue;
                }


                if ($key === 'favicon') {

                    if ($request->hasFile('settings.favicon')) {

                        $file = $request->file('settings.favicon');

                        if (
                            !empty($setting->value) &&
                            Storage::disk('public')->exists($setting->value)
                        ) {
                            Storage::disk('public')->delete(
                                $setting->value
                            );
                        }

                        $path = $file->store(
                            'favicons',
                            'public'
                        );


                        $setting->value = $path;
                        $setting->save();
                    }

                    continue;
                }


                $value = $request->input(
                    'settings.' . $key
                );


                if ($setting->type === 'boolean') {

                    $value = $request->boolean(
                        'settings.' . $key
                    )
                        ? '1'
                        : '0';
                }


                elseif ($setting->type === 'string') {

                    if (is_array($value)) {

                        throw ValidationException::withMessages([
                            'settings.' . $key => [
                                'Invalid value.'
                            ],
                        ]);
                    }

                    $value = $value !== null
                        ? trim((string) $value)
                        : '';
                }

                elseif ($setting->type === 'decimal') {

                    if (
                        $value === null ||
                        $value === '' ||
                        !is_numeric($value)
                    ) {
                        throw ValidationException::withMessages([
                            'settings.' . $key => [
                                $key . ' must be a valid number.'
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

                elseif ($setting->type === 'integer') {

                    if (
                        $value === null ||
                        $value === '' ||
                        filter_var(
                            $value,
                            FILTER_VALIDATE_INT
                        ) === false
                    ) {
                        throw ValidationException::withMessages([
                            'settings.' . $key => [
                                $key . ' must be a valid integer.'
                            ],
                        ]);
                    }

                    $value = (string) (int) $value;
                }

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

                        if (
                            json_last_error() !== JSON_ERROR_NONE
                        ) {
                            throw ValidationException::withMessages([
                                'settings.' . $key => [
                                    'Invalid JSON format.'
                                ],
                            ]);
                        }
                    }
                }

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
