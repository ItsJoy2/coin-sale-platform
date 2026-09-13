@extends('admin.layouts.app')

@section('title', 'User Details')

@section('content')

<div class="container-fluid">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- ============================= --}}
    {{-- TOP TWO CARDS --}}
    {{-- ============================= --}}
    <div class="row g-3">

        {{-- ============================= --}}
        {{-- LEFT: USER PROFILE --}}
        {{-- ============================= --}}
        <div class="col-lg-7">

            <div class="card h-100 shadow-sm">

                <div class="card-header">
                    <strong>
                        <i class="fas fa-user me-1"></i>
                        User Profile
                    </strong>
                </div>

                <div class="card-body">

                    {{-- Profile Header --}}
                    <div class="d-flex align-items-center mb-4">

                        <div class="user-avatar me-3">
                            {{ strtoupper(substr($user->name ?: $user->wallet_address, 0, 1)) }}
                        </div>

                        <div>
                            <h5 class="mb-1">
                                {{ $user->name ?: 'N/A' }}
                            </h5>

                            <div class="text-muted small">
                                {{ $user->email ?: 'No email' }}
                            </div>
                        </div>

                    </div>


                    {{-- Profile Information --}}
                    <div class="row g-3">

                        {{-- Name --}}
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">
                                    Name
                                </div>

                                <div class="info-value">
                                    {{ $user->name ?: 'N/A' }}
                                </div>
                            </div>
                        </div>


                        {{-- Email --}}
                        <div class="col-md-6">
                            <div class="info-box">
                                <div class="info-label">
                                    Email
                                </div>

                                <div class="info-value text-break">
                                    {{ $user->email ?: 'N/A' }}
                                </div>
                            </div>
                        </div>


                        {{-- Wallet Address --}}
                        <div class="col-12">
                            <div class="info-box">

                                <div class="info-label">
                                    Wallet Address
                                </div>

                                <div class="d-flex align-items-center gap-2">

                                    <div
                                        class="info-value wallet-address flex-grow-1"
                                        id="walletAddress"
                                    >
                                        {{ $user->wallet_address }}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-wallet" data-wallet="{{ $user->wallet_address }}" title="Copy wallet address" >
                                        <i class="fas fa-copy"></i>
                                    </button>

                                </div>

                            </div>
                        </div>


                        {{-- Referral Code --}}
                        <div class="col-md-6">
                            <div class="info-box">

                                <div class="info-label">
                                    Referral Code
                                </div>

                                @if($user->referral_code)
                                    <div>
                                        <span class="badge bg-primary">
                                            {{ $user->referral_code }}
                                        </span>
                                    </div>
                                @else
                                    <div class="info-value">
                                        N/A
                                    </div>
                                @endif

                            </div>
                        </div>


                        {{-- Referred By --}}
                        <div class="col-md-6">
                            <div class="info-box">

                                <div class="info-label">
                                    Referred By
                                </div>

                                @if($user->referrer)

                                    @php
                                        $referrerWallet = $user->referrer->wallet_address;
                                        $shortReferrerWallet = strlen($referrerWallet) > 12
                                            ? substr($referrerWallet, 0, 8) . '.....' . substr($referrerWallet, -8)
                                            : $referrerWallet;
                                    @endphp

                                    {{-- Referral User Wallet --}}
                                    <div class="d-flex align-items-center gap-2">

                                        <div
                                            class="info-value flex-grow-1"
                                            id="referralWallet"
                                            data-wallet="{{ $referrerWallet }}"
                                            title="{{ $referrerWallet }}"
                                            style="font-size: 11px;"
                                        >
                                            {{ $shortReferrerWallet }}
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary copy-wallet"
                                            data-wallet="{{ $referrerWallet }}"
                                            title="Copy wallet address"
                                        >
                                            <i class="fas fa-copy"></i>
                                        </button>

                                    </div>

                                @else

                                    <div class="info-value">
                                        Direct / None
                                    </div>

                                @endif

                            </div>
                        </div>


                        {{-- Address --}}
                        <div class="col-12">
                            <div class="info-box">

                                <div class="info-label">
                                    Address
                                </div>

                                <div class="info-value">
                                    {{ $user->address ?: 'N/A' }}
                                </div>

                            </div>
                        </div>


                        {{-- Joined At --}}
                        <div class="col-md-6">
                            <div class="info-box">

                                <div class="info-label">
                                    Joined At
                                </div>

                                <div
                                    class="info-value local-datetime"
                                    data-datetime="{{ optional($user->created_at)->toISOString() }}"
                                >
                                    {{ optional($user->created_at)->format('M d, Y h:i A') }}
                                </div>

                            </div>
                        </div>


                        {{-- Account Type --}}
                        <div class="col-md-6">
                            <div class="info-box">

                                <div class="info-label">
                                    Account Type
                                </div>

                                <div>
                                    <span class="badge bg-primary">
                                        {{ ucfirst($user->role ?? 'user') }}
                                    </span>
                                </div>

                            </div>
                        </div>

                    </div>


                    {{-- Profile Buttons --}}
                    <div class="mt-4 pt-3 border-top">

                        <button
                            type="button"
                            class="btn btn-warning btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#editUserModal"
                        >
                            <i class="fas fa-edit me-1"></i>
                            Edit Profile
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#changePasswordModal"
                        >
                            <i class="fas fa-key me-1"></i>
                            Change Password
                        </button>

                    </div>

                </div>
            </div>

        </div>


        {{-- ============================= --}}
        {{-- RIGHT: WALLET ADJUSTMENT --}}
        {{-- ============================= --}}
        <div class="col-lg-5">

            <div class="card h-100 shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <strong>
                        <i class="fas fa-wallet me-1"></i>
                        Wallet Adjustment
                    </strong>

                    <span class="badge bg-primary">
                        MIND
                    </span>

                </div>


                <div class="card-body">

                    {{-- Current Balance --}}
                    <div class="balance-card mb-4">

                        <div class="balance-label">
                            Current MIND Balance
                        </div>

                        <div class="balance-value">
                            {{ number_format((float) $user->mind_balance, 3) }}
                            <span>MIND</span>
                        </div>

                    </div>


                    {{-- Wallet --}}
                    {{-- <div class="wallet-box mb-4">

                        <div class="info-label">
                            Wallet Address
                        </div>

                        <div class="wallet-address-small text-break">
                            {{ $user->wallet_address }}
                        </div>

                    </div> --}}


                    {{-- Add / Deduct --}}
                    <div class="row g-2">

                        <div class="col-12">

                            <button
                                type="button"
                                class="btn btn-warning w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#balanceAdjustModal"
                                data-action="add"
                            >
                                <i class="fas fa-plus me-1"></i>/<i class="fas fa-minus me-1"></i>
                                Balance Adjustment
                            </button>

                        </div>

                    </div>


                    {{-- Warning --}}
                    <div class="alert alert-warning mt-4 mb-0 small">

                        <i class="fas fa-exclamation-triangle me-1"></i>

                        Balance adjustment will immediately update the
                        user's MIND balance and create a transaction history.

                    </div>

                </div>
            </div>

        </div>

    </div>

@include('admin.pages.users.models.__update')

@include('admin.pages.users.models.__password-update')

@include('admin.pages.users.models.__wallet-adjust')

</div>


<script>

function setBalanceAction(action)
{
    const actionInput = document.getElementById('balanceAction');
    const modalTitle = document.getElementById('balanceModalTitle');
    const submitBtn = document.getElementById('balanceSubmitBtn');

    const addBtn = document.getElementById('addBalanceBtn');
    const deductBtn = document.getElementById('deductBalanceBtn');

    const deductWarning = document.getElementById('deductWarning');

    actionInput.value = action;

    if (action === 'add') {

        // Title
        modalTitle.textContent = 'Add MIND';

        // Add button
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML =
            '<i class="fas fa-plus me-1"></i> Add MIND';

        // Toggle buttons
        addBtn.className = 'btn btn-success';
        deductBtn.className = 'btn btn-outline-danger';

        // Warning
        deductWarning.classList.add('d-none');

    } else {

        // Title
        modalTitle.textContent = 'Deduct MIND';

        // Deduct button
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML =
            '<i class="fas fa-minus me-1"></i> Deduct MIND';

        // Toggle buttons
        addBtn.className = 'btn btn-outline-success';
        deductBtn.className = 'btn btn-danger';

        // Warning
        deductWarning.classList.remove('d-none');
    }
}


// Reset modal to Add mode whenever it opens
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('balanceAdjustModal');

    if (modal) {

        modal.addEventListener('show.bs.modal', function () {
            setBalanceAction('add');
        });

    }

});

</script>


@endsection
