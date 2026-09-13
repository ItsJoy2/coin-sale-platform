@extends('admin.layouts.app')

@section('title', 'User Management')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-12">

            <div class="card mb-4">

                <div class="card-header fw-bold">
                    User Management
                </div>

                <div class="card-body">


                    {{-- Search --}}
                    <form method="GET"
                        action="{{ route('admin.users.index') }}">

                        <div class="mb-3 d-flex gap-2"
                            style="max-width: 700px; max-height: 40px;">

                            <input  type="search" class="form-control"  name="search"  value="{{ request('search') }}"  placeholder="Search name, email, wallet or referral code..." >

                            <button type="submit"
                                    class="btn btn-primary d-flex align-items-center gap-1">

                                <i class="fas fa-search"></i>Search

                            </button>

                            @if(request()->filled('search'))

                                <a href="{{ route('admin.users.index') }}"
                                class="btn btn-secondary">

                                    Reset

                                </a>

                            @endif

                        </div>

                    </form>



                    {{-- Users Table --}}
                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle mb-0">

                            <thead>
                                <tr class="text-center">

                                    <th>#</th>
                                    <th>User</th>
                                    <th>Wallet Address</th>
                                    <th>Email</th>
                                    <th>Referral Code</th>
                                    <th>MIND Balance</th>
                                    <th>Joined At</th>
                                    <th>Actions</th>

                                </tr>
                            </thead>

                            <tbody>

                            @forelse($users as $user)

                                <tr>

                                    {{-- Serial --}}
                                    <td class="text-center">
                                        {{ $users->firstItem() + $loop->index }}
                                    </td>

                                    {{-- User --}}
                                    <td>
                                        <div class="fw-semibold text-nowrap">
                                            {{ $user->name ?: 'N/A' }}
                                        </div>
                                    </td>

                                    {{-- Wallet Address --}}
                                    <td>
                                        <div class="d-flex align-items-center gap-2">

                                            @php
                                                $wallet = $user->wallet_address;

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
                                    </td>

                                    {{-- Email --}}
                                    <td>
                                        @if($user->email)
                                            <span class="text-nowrap">
                                                {{ $user->email }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                N/A
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Referral --}}
                                    <td>
                                        @if($user->referral_code)

                                            <span class="badge bg-info text-nowrap">
                                                {{ $user->referral_code }}
                                            </span>

                                        @else

                                            <span class="text-muted">
                                                N/A
                                            </span>

                                        @endif
                                    </td>

                                    {{-- MIND Balance --}}
                                    <td class="text-end text-nowrap">

                                        <strong>
                                            {{ number_format((float) $user->mind_balance, 3) }}
                                        </strong>

                                        <small class="text-muted">
                                            MIND
                                        </small>

                                    </td>

                                    {{-- Joined --}}
                                    <td class="text-nowrap">

                                        @if($user->created_at)

                                            <span
                                                class="local-datetime"
                                                data-datetime="{{ $user->created_at->utc()->toIso8601String() }}"
                                            >
                                                Loading...
                                            </span>

                                        @else

                                            N/A

                                        @endif

                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-center">

                                        <div class="d-flex gap-2 justify-content-center flex-nowrap">

                                            <a
                                                href="{{ route('admin.users.edit', $user->id) }}"
                                                class="btn btn-sm btn-outline-warning flex-shrink-0"
                                                title="Edit User"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            No users found.
                                        </div>
                                    </td>
                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">

                        {{ $users->links('admin.components.pagination') }}

                    </div>

                </div>

            </div>

        </div>
    </div>
</div>


<script>
    function copyReferralWallet() {

    const wallet = document.getElementById('referralWallet').value;

    if (!wallet || wallet === 'N/A') {
        return;
    }

    navigator.clipboard.writeText(wallet).then(function () {

        const button = event.currentTarget;

        const oldHtml = button.innerHTML;

        button.innerHTML = '<i class="fas fa-check"></i>';

        setTimeout(function () {
            button.innerHTML = oldHtml;
        }, 1500);

    });

}
</script>
@endsection
