@extends('admin.layouts.app')

@section('title', 'Transactions')

@section('content')

<div class="container">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                Transactions
            </h4>

            <p class="text-muted mb-0">
                View all user transactions
            </p>
        </div>
    </div>


    {{-- Search & Filter --}}
    <div class="card mb-4">
        <div class="card-body">

            <form method="GET"
                  action="{{ route('admin.transactions.index') }}">

                <div class="row g-3">

                    {{-- Search --}}
                    <div class="col-md-5">

                        <label class="form-label">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Name, Email, Wallet Address or Order ID"
                        >

                    </div>


                    {{-- Type --}}
                    <div class="col-md-3">

                        <label class="form-label">
                            Transaction Type
                        </label>

                        <select name="type"
                                class="form-select">

                            <option value="">
                                All Types
                            </option>

                            <option value="purchase"
                                {{ request('type') === 'purchase' ? 'selected' : '' }}>
                                Purchase
                            </option>

                            <option value="tier_bonus"
                                {{ request('type') === 'tier_bonus' ? 'selected' : '' }}>
                                Tier Bonus
                            </option>

                            <option value="coupon_bonus"
                                {{ request('type') === 'coupon_bonus' ? 'selected' : '' }}>
                                Coupon Bonus
                            </option>

                            <option value="referral_bonus"
                                {{ request('type') === 'referral_bonus' ? 'selected' : '' }}>
                                Referral Bonus
                            </option>

                            <option value="withdrawal"
                                {{ request('type') === 'withdrawal' ? 'selected' : '' }}>
                                Withdrawal
                            </option>

                            <option value="admin_adjustment"
                                {{ request('type') === 'admin_adjustment' ? 'selected' : '' }}>
                                Admin Adjustment
                            </option>

                        </select>

                    </div>


                    {{-- Status --}}
                    <div class="col-md-2">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status"
                                class="form-select">

                            <option value="">
                                All
                            </option>

                            <option value="completed"
                                {{ request('status') === 'completed' ? 'selected' : '' }}>
                                Completed
                            </option>

                            <option value="pending"
                                {{ request('status') === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                            <option value="cancelled"
                                {{ request('status') === 'cancelled' ? 'selected' : '' }}>
                                Failed
                            </option>

                        </select>

                    </div>


                    {{-- Buttons --}}
                    <div class="col-md-1 d-flex align-items-end">

                        <button type="submit"
                                class="btn btn-primary w-100">

                            <i class="fas fa-search"></i>

                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>


    {{-- Transactions Table --}}
    <div class="card">

        <div class="card-header">
            <strong>
                Transaction History
            </strong>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover table-bordered mb-0">

                    <thead>
                        <tr>

                            <th>#</th>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Wallet Address</th>
                            <th>Type</th>
                            <th>MIND</th>
                            <th>USDT</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th>Date</th>

                        </tr>
                    </thead>


                    <tbody>

                    @forelse($transactions as $transaction)

                        <tr>

                            {{-- ID --}}
                            <td>
                                {{ $transactions->firstItem() + $loop->index }}
                            </td>


                            {{-- Order ID --}}
                            <td>
                                <span class="fw-semibold">
                                    {{ $transaction->order_id }}
                                </span>
                            </td>


                            {{-- User --}}
                            <td>

                                <div>
                                    <strong>
                                        {{ $transaction->user?->email ?: 'N/A' }}
                                    </strong>
                                </div>

                            </td>


                            {{-- Wallet --}}
                            <td>

                                @if($transaction->user?->wallet_address)
                                <div class="d-flex align-items-center gap-2">

                                            @php
                                                $wallet = $transaction->user?->wallet_address;

                                                $shortWallet = strlen($wallet) > 12
                                                    ? substr($wallet, 0, 6) . '...' . substr($wallet, -6)
                                                    : $wallet;
                                            @endphp

                                            <span
                                                title="{{ $wallet }}"
                                                style="font-family: monospace;"
                                                class="text-nowrap"
                                            >
                                                {{ $shortWallet }}
                                            </span>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary copy-wallet flex-shrink-0"
                                                data-wallet="{{ $wallet }}"
                                                title="Copy wallet address"
                                            >
                                                <i class="fas fa-copy"></i>
                                            </button>

                                        </div>
                                @else
                                    <span class="text-muted">
                                        N/A
                                    </span>
                                @endif

                            </td>


                            {{-- Type --}}
                            <td>

                                @php
                                    $typeClass = match($transaction->type) {
                                        'purchase' => 'bg-primary',
                                        'referral_bonus' => 'bg-success',
                                        'coupon_bonus' => 'bg-info',
                                        'tier_bonus' => 'bg-warning text-dark',
                                        'withdrawal' => 'bg-danger',
                                        'admin_adjustment' => 'bg-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $typeClass }}">
                                    {{ ucwords(str_replace('_', ' ', $transaction->type)) }}
                                </span>

                            </td>


                            {{-- MIND --}}
                            <td class="text-nowrap">

                                @php
                                    $mind = (float) $transaction->amount_mind;
                                @endphp

                                <span class="{{ $mind < 0 ? 'text-danger' : 'text-success' }} fw-semibold">

                                    {{ $mind > 0 ? '+' : '' }}
                                    {{ number_format($mind, 3) }}

                                </span>

                            </td>


                            {{-- USDT --}}
                            <td>

                                {{ number_format(
                                    (float) $transaction->amount_usdt,
                                    3
                                ) }}

                            </td>


                            {{-- Rate --}}
                            <td>

                                {{ number_format(
                                    (float) $transaction->rate_applied,
                                    2
                                ) }}

                            </td>


                            {{-- Status --}}
                            <td>

                                @php
                                    $statusClass = match($transaction->status) {
                                        'completed' => 'bg-success',
                                        'pending' => 'bg-warning text-dark',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>

                            </td>


                            {{-- Date --}}
                            <td class="text-nowrap">

                                @if($transaction->created_at)

                                    <span
                                        class="local-datetime"
                                        data-datetime="{{ $transaction->created_at->toISOString() }}"
                                    >
                                        {{ $transaction->created_at->format('Y-m-d H:i:s') }}
                                    </span>

                                @else
                                    —
                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="11"
                                class="text-center py-5 text-muted">
                                <div>
                                    No transactions found.
                                </div>

                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Pagination --}}
        @if($transactions->hasPages())

            <div class="card-footer">

                {{ $transactions->links() }}

            </div>

        @endif

    </div>

</div>

@endsection
