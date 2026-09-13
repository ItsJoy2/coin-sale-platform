<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    /**
     * Coupon List
     */
    public function index()
    {
        $coupons = Coupon::query()
            ->latest('id')
            ->paginate(15);

        return view(
            'admin.pages.coupons.index',
            compact('coupons')
        );
    }

    /**
     * Create Coupon Form
     */
    public function create()
    {
        return view('admin.pages.coupons.create');
    }

    /**
     * Store Coupon
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'unique:coupons,code',
            ],

            'discount_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'extra_bonus_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'max_uses' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'expires_at' => [
                'nullable',
                'date',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $validated['used_count'] = 0;
        $validated['is_active'] = $request->boolean('is_active');

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    /**
     * Edit Coupon
     */
    public function edit(Coupon $coupon)
    {
        return view(
            'admin.pages.coupons.edit',
            compact('coupon')
        );
    }

    /**
     * Update Coupon
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => ['required','string','max:100',Rule::unique('coupons', 'code')->ignore($coupon->id),],
            'discount_percentage' => ['required','numeric','min:0','max:100',],
            'extra_bonus_percentage' => ['nullable','numeric','min:0','max:100',],
            'max_uses' => ['nullable','integer','min:1',],
            'used_count' => ['required', 'integer','min:0', ],
            'expires_at' => ['nullable','date',],
            'is_active' => ['nullable','boolean',],
        ]);

        if ($validated['used_count'] < $coupon->used_count) {
            return back()->withErrors([
                    'used_count' =>'Used count cannot be lower than the current used count.'
                ])
                ->withInput();
        }

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');

        $coupon->update($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    /**
     * Delete Coupon
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }

    /**
     * Toggle Active Status
     */
    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update([
            'is_active' => !$coupon->is_active,
        ]);

        return back()->with(
            'success',
            $coupon->is_active
                ? 'Coupon activated successfully.'
                : 'Coupon deactivated successfully.'
        );
    }
}
