@extends('admin.layouts.app')

@section('title', 'My Profile')

@section('content')

<div class="container">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-circle me-2"></i>
                My Profile
            </h4>

            <p class="text-muted mb-0">
                Manage your admin account information and password.
            </p>
        </div>
    </div>

    @include('admin.components.alerts')

    <div class="row g-4">

        {{-- Profile Information --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm">

                <div class="card-header border-bottom py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-user-edit me-2"></i>
                        Profile Information
                    </h5>
                </div>

                <div class="card-body p-4">

                    <form method="POST"
                          action="{{ route('admin.profile.update') }}">

                        @csrf
                        @method('PUT')

                        <div class="row g-3">

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Name
                                </label>

                                <input type="text"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $admin->name) }}"
                                       placeholder="Enter your name">

                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>


                            {{-- Email --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Email
                                </label>

                                <input type="email"
                                       name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $admin->email) }}"
                                       placeholder="Enter your email">

                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>


                            {{-- Wallet Address --}}


                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Wallet Address
                                </label>

                                <div class="input-group">

                                    <input type="text"
                                        name="wallet_address"
                                        class="form-control @error('wallet_address') is-invalid @enderror"
                                        value="{{ old('wallet_address', $admin->wallet_address) }}"
                                        placeholder="Enter wallet address">

                                    <button type="button"
                                            class="btn btn-outline-secondary copy-wallet"
                                            data-wallet="{{ old('wallet_address', $admin->wallet_address) }}"
                                            title="Copy wallet address">

                                        <i class="fas fa-copy"></i>

                                    </button>

                                </div>

                                @error('wallet_address')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Referral Code --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Referral Code
                                </label>

                                <input type="text"
                                       name="referral_code"
                                       class="form-control"
                                       value="{{ old('referral_code', $admin->referral_code) }}"
                                       readonly
                                       disabled>
                            </div>


                            {{-- Role --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Role
                                </label>

                                <input type="text"
                                       class="form-control"
                                       value="{{ ucfirst($admin->role) }}"
                                       readonly
                                       disabled>
                            </div>


                            {{-- Address --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Address
                                </label>

                                <textarea name="address"
                                          rows="3"
                                          class="form-control @error('address') is-invalid @enderror"
                                          placeholder="Enter address">{{ old('address', $admin->address) }}</textarea>

                                @error('address')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>


                            {{-- Balance --}}
                            {{-- <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    MIND Balance
                                </label>

                                <input type="text"
                                       class="form-control"
                                       value="{{ number_format((float) $admin->mind_balance, 8) }}"
                                       readonly
                                       disabled>
                            </div> --}}

                        </div>


                        <div class="mt-4 pt-3 border-top">

                            <button type="submit"
                                    class="btn btn-primary px-4">

                                <i class="fas fa-save me-2"></i>
                                Update Profile

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        {{-- Password Change --}}
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm">

                <div class="card-header border-bottom py-3">

                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-lock me-2"></i>
                        Change Password
                    </h5>

                </div>

                <div class="card-body p-4">

                    <form method="POST"
                        action="{{ route('admin.profile.password.update') }}">

                        @csrf
                        @method('PUT')


                        {{-- Current Password --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Current Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                    name="current_password"
                                    id="currentPassword"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    placeholder="Enter current password"
                                    autocomplete="current-password">

                                <button type="button"
                                        class="btn btn-outline-secondary toggle-password"
                                        data-target="currentPassword"
                                        title="Show password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                            @error('current_password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- New Password --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                New Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                    name="password"
                                    id="newPassword"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Minimum 8 characters"
                                    autocomplete="new-password">

                                <button type="button"
                                        class="btn btn-outline-secondary toggle-password"
                                        data-target="newPassword"
                                        title="Show password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                            @error('password')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Confirm Password --}}
                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Confirm New Password
                            </label>

                            <div class="input-group">

                                <input type="password"
                                    name="password_confirmation"
                                    id="confirmPassword"
                                    class="form-control"
                                    placeholder="Confirm new password"
                                    autocomplete="new-password">

                                <button type="button"
                                        class="btn btn-outline-secondary toggle-password"
                                        data-target="confirmPassword"
                                        title="Show password">

                                    <i class="fas fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        {{-- <div class="alert alert-light border small">

                            <i class="fas fa-info-circle me-1"></i>

                            Password must contain at least
                            <strong>8 characters</strong>.

                        </div> --}}


                        <button type="submit"
                                class="btn btn-dark w-100">

                            <i class="fas fa-key me-2"></i>
                            Change Password

                        </button>

                    </form>

                </div>

            </div>


            {{-- Account Info --}}
            {{-- <div class="card border-0 shadow-sm mt-4">

                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-shield-alt me-2"></i>
                        Account Information
                    </h6>
                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Account ID
                        </span>

                        <strong>
                            #{{ $admin->id }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            Role
                        </span>

                        <span class="badge bg-primary">
                            {{ ucfirst($admin->role) }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted">
                            Member Since
                        </span>

                        <strong>
                            {{ $admin->created_at?->format('d M Y') }}
                        </strong>
                    </div>

                </div>

            </div> --}}

        </div>

    </div>

</div>

<script>
document.querySelectorAll('.toggle-password').forEach(function (button) {

    button.addEventListener('click', function () {

        const targetId = this.dataset.target;
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (!input) {
            return;
        }

        if (input.type === 'password') {

            input.type = 'text';

            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');

            this.setAttribute('title', 'Hide password');

        } else {

            input.type = 'password';

            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');

            this.setAttribute('title', 'Show password');
        }

    });

});
</script>

@endsection
