<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display all users except admins.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->where('role', '!=', 'admin');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('wallet_address', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.users.index', compact('users'));
    }


    /**
     * Show user details page.
     */
    public function show(User $user)
    {
        // Admin user cannot be viewed from user management.
        if ($user->role === 'admin') {
            abort(404);
        }

        return view('admin.pages.users.show', compact('user'));
    }


    /**
     * Edit user details page.
     *
     * Keeping this method because your existing route may use it.
     */
    public function edit(User $user)
    {
        if ($user->role === 'admin') {
            abort(403, 'Admin user cannot be edited.');
        }

        return view('admin.pages.users.show', compact('user'));
    }


    /**
     * Update user information from admin panel.
     */
    public function update(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            abort(403, 'Admin user cannot be updated.');
        }

        $validated = $request->validate([
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'wallet_address' => [
                'required',
                'string',
                'regex:/^0x[a-fA-F0-9]{40}$/',
                Rule::unique('users', 'wallet_address')->ignore($user->id),
            ],

            'address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'referral_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'referral_code')->ignore($user->id),
            ],
        ]);

        $user->name = $validated['name'] ?? null;
        $user->email = $validated['email'] ?? null;
        $user->wallet_address = $validated['wallet_address'];
        $user->address = $validated['address'] ?? null;
        $user->referral_code = $validated['referral_code'] ?? null;

        // Role is intentionally NOT updated.
        $user->save();

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', 'User information updated successfully.');
    }


    /**
     * Update user password from admin panel.
     */
    public function updatePassword(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            abort(403, 'Admin password cannot be changed from here.');
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', 'User password updated successfully.');
    }


    /**
     * Add or deduct MIND balance from admin panel.
     */
    public function adjustBalance(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            abort(403, 'Admin balance cannot be adjusted.');
        }

        $validated = $request->validate([
            'action' => ['required',Rule::in(['add', 'deduct']), ],
            'amount' => ['required','numeric','gt:0','max:999999999999.99999999',],
            'reason' => ['required','string','max:500',],
        ]);

        $amount = number_format(
            (float) $validated['amount'],
            8,
            '.',
            ''
        );

        DB::transaction(function () use ($user, $validated, $amount) {

            // Lock user row to prevent balance race condition.
            $lockedUser = User::query()
                ->where('id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = (string) $lockedUser->mind_balance;

            /*
             * DEDUCT MIND
             */
            if ($validated['action'] === 'deduct') {

                if (bccomp($amount, $currentBalance, 8) > 0) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'Insufficient MIND balance. Current balance: '
                            . number_format((float) $currentBalance, 3)
                            . ' MIND.'
                        ],
                    ]);
                }

                $newBalance = bcsub(
                    $currentBalance,
                    $amount,
                    8
                );

                $transactionAmount = '-' . $amount;

                $description = 'Admin deducted MIND: '
                    . $validated['reason'];
            }

            /*
             * ADD MIND
             */
            else {

                $newBalance = bcadd(
                    $currentBalance,
                    $amount,
                    8
                );

                $transactionAmount = $amount;

                $description = 'Admin added MIND: '
                    . $validated['reason'];
            }

            // Update user's MIND balance.
            $lockedUser->mind_balance = $newBalance;
            $lockedUser->save();

            /*
             * Create transaction history.
             */
            Transaction::create([
                'user_id' => $lockedUser->id,

                'purchase_id' => null,

                'type' => 'admin_adjustment',

                'amount_mind' => $transactionAmount,

                'amount_usdt' => 0,

                // Admin who performed the adjustment.
                'source_user_id' => auth()->id(),

                'rate_applied' => 0,

                'description' => $description,

                'status' => 'completed',

                'created_at' => now(),
            ]);
        });

        $message = $validated['action'] === 'add'
            ? 'MIND balance added successfully.'
            : 'MIND balance deducted successfully.';

        return redirect()
            ->route('admin.users.show', $user->id)
            ->with('success', $message);
    }
}
