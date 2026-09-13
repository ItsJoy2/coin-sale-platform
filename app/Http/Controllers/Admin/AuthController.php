<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.pages.authentication.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required'],
            'password' => ['required'],
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'user_name';

        $credentials = [
            $loginField => $request->login,
            'password'  => $request->password,
            'role'      => 'admin',
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    'login' => 'Invalid credentials or admin access required.',
                ]);
        }

        $request->session()->regenerate();

        // Debug
        // dd(Auth::check(), Auth::user());

        return redirect()->route('admin.dashboard');
    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function profile()
    {
        $admin = Auth::user();

        return view('admin.pages.authentication.profile', compact('admin'));
    }
    public function update(Request $request)
    {
        $admin = $request->user();

        $validated = $request->validate([
            'name' => [ 'required', 'string', 'max:255', ],
            'email' => [ 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id),],
            'wallet_address' => [ 'nullable', 'string', 'max:42', Rule::unique('users', 'wallet_address')->ignore($admin->id), ],
            'address' => [ 'nullable', 'string', 'max:500', ],
        ]);

        $admin->update($validated);

        return back()->with('success','Profile updated successfully.');
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $admin = Auth::user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.'
            ]);
        }

        $admin->password = $request->password;
        $admin->save();

        return back()->with('success', 'Password changed successfully.');
    }
}
