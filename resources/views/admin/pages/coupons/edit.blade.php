@extends('admin.layouts.app')

@section('title', 'Edit Coupon')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit me-2"></i>
                Edit Coupon
            </h4>

            <p class="text-muted mb-0">
                Update coupon settings.
            </p>

        </div>

        {{-- <a href="{{ route('admin.coupons.index') }}"
           class="btn btn-outline-secondary">

            <i class="fas fa-arrow-left me-2"></i>
            Back

        </a> --}}

    </div>


    {{-- Errors --}}
    @if($errors->any())

        <div class="alert alert-danger">

            <div class="fw-bold mb-2">
                <i class="fas fa-exclamation-circle me-2"></i>
                Please fix the following errors:
            </div>

            <ul class="mb-0 ps-3">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <div class="row justify-content-center">

        <div class="col-xl-8 col-lg-10">

            <div class="card border-0 shadow-sm">

                <div class="card-header py-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="mb-0 fw-bold">
                            Coupon Information
                        </h5>

                        <span class="badge bg-light text-dark">
                            Code: {{ $coupon->code }}
                        </span>

                    </div>

                </div>


                <div class="card-body p-4">

                    <form method="POST"
                          action="{{ route('admin.coupons.update', $coupon) }}">

                        @csrf
                        @method('PUT')

                        <div class="row g-4">

                            {{-- Code --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Coupon Code
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text"
                                       name="code"
                                       class="form-control @error('code') is-invalid @enderror"
                                       value="{{ old('code', $coupon->code) }}"
                                       maxlength="100"
                                       required>

                                @error('code')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- Discount --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Discount Percentage
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">

                                    <input type="number"
                                           name="discount_percentage"
                                           class="form-control @error('discount_percentage') is-invalid @enderror"
                                           value="{{ old('discount_percentage', $coupon->discount_percentage) }}"
                                           min="0"
                                           max="100"
                                           step="0.01"
                                           required>

                                    <span class="input-group-text">
                                        %
                                    </span>

                                </div>

                                @error('discount_percentage')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- Extra Bonus --}}
                            {{-- <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Extra Bonus Percentage
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">

                                    <input type="number"
                                           name="extra_bonus_percentage"
                                           class="form-control @error('extra_bonus_percentage') is-invalid @enderror"
                                           value="{{ old('extra_bonus_percentage', $coupon->extra_bonus_percentage) }}"
                                           min="0"
                                           max="100"
                                           step="0.01"
                                           required>

                                    <span class="input-group-text">
                                        %
                                    </span>

                                </div>

                                @error('extra_bonus_percentage')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div> --}}


                            {{-- Max Uses --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Maximum Uses
                                </label>

                                <input type="number"
                                       name="max_uses"
                                       class="form-control @error('max_uses') is-invalid @enderror"
                                       value="{{ old('max_uses', $coupon->max_uses) }}"
                                       min="1"
                                       placeholder="Leave empty for unlimited">

                                @error('max_uses')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- Used Count --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Used Count
                                </label>

                                <input type="number"
                                       name="used_count"
                                       class="form-control @error('used_count') is-invalid @enderror"
                                       value="{{ old('used_count', $coupon->used_count) }}"
                                       min="{{ $coupon->used_count }}">

                                @error('used_count')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="text-muted">
                                    Current usage: {{ $coupon->used_count }}
                                </small>

                            </div>


                            {{-- Expiry --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Expiry Date
                                </label>

                                <input type="datetime-local"
                                       name="expires_at"
                                       class="form-control @error('expires_at') is-invalid @enderror"
                                       value="{{ old(
                                           'expires_at',
                                           $coupon->expires_at?->format('Y-m-d\TH:i')
                                       ) }}">

                                @error('expires_at')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>


                            {{-- Status --}}
                            <div class="col-md-6">

                                <label class="form-label fw-semibold d-block">
                                    Status
                                </label>

                                <div class="form-check form-switch mt-2">

                                    <input type="hidden"
                                           name="is_active"
                                           value="0">

                                    <input type="checkbox"
                                           name="is_active"
                                           value="1"
                                           class="form-check-input"
                                           id="isActive"
                                           {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>

                                    <label class="form-check-label"
                                           for="isActive">

                                        Active

                                    </label>

                                </div>

                            </div>


                            {{-- Current Status --}}
                            <div class="col-12">

                                <div class="alert
                                    @if($coupon->isValid())
                                        alert-success
                                    @elseif(!$coupon->is_active)
                                        alert-secondary
                                    @else
                                        alert-warning
                                    @endif
                                    mb-0">

                                    @if($coupon->isValid())

                                        <i class="fas fa-check-circle me-2"></i>
                                        This coupon is currently valid.

                                    @elseif(!$coupon->is_active)

                                        <i class="fas fa-pause-circle me-2"></i>
                                        This coupon is inactive.

                                    @elseif(
                                        $coupon->expires_at &&
                                        $coupon->expires_at->isPast()
                                    )

                                        <i class="fas fa-calendar-times me-2"></i>
                                        This coupon has expired.

                                    @else

                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        This coupon has reached its usage limit.

                                    @endif

                                </div>

                            </div>

                        </div>


                        <div class="d-flex justify-content-end gap-2 mt-4 pt-4 border-top">

                            <a href="{{ route('admin.coupons.index') }}"
                               class="btn btn-light">

                                Cancel

                            </a>

                            <button type="submit"
                                    class="btn btn-primary px-4">

                                <i class="fas fa-save me-2"></i>
                                Update Coupon

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
