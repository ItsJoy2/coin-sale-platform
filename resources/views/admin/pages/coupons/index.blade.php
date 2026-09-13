@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1 fw-bold">
                Coupons
            </h4>

            <p class="text-muted mb-0">
                Manage discount and bonus coupons.
            </p>
        </div>

        <a href="{{ route('admin.coupons.create') }}"
           class="btn btn-primary">

            <i class="fas fa-plus me-2"></i>
            Create Coupon

        </a>

    </div>

@include('admin.components.alerts')


    {{-- Table --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header border-bottom py-3">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0 fw-bold">
                    All Coupons
                </h5>

                <span class="badge bg-light text-dark">
                    {{ $coupons->total() }} Total
                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="ps-4">
                                #
                            </th>

                            <th>
                                Coupon
                            </th>

                            <th>
                                Discount
                            </th>

                            {{-- <th>
                                Extra Bonus
                            </th> --}}

                            <th>
                                Usage
                            </th>

                            <th>
                                Expiry
                            </th>

                            <th>
                                Status
                            </th>

                            <th class="text-end pe-4">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($coupons as $coupon)

                            <tr>

                                <td class="ps-4">
                                    {{ $coupons->firstItem() + $loop->index }}
                                </td>


                                {{-- Coupon Code --}}
                                <td>

                                    <div class="fw-bold">
                                        {{ $coupon->code }}
                                    </div>

                                    {{-- <small class="text-muted">
                                        ID: #{{ $coupon->id }}
                                    </small> --}}

                                </td>


                                {{-- Discount --}}
                                <td>

                                    <span class="badge bg-primary-subtle text-primary">

                                        {{ number_format((float) $coupon->discount_percentage, 2) }}%

                                    </span>

                                </td>


                                {{-- Extra Bonus --}}
                                {{-- <td>

                                    <span class="badge bg-success-subtle text-success">

                                        {{ number_format((float) $coupon->extra_bonus_percentage, 2) }}%

                                    </span>

                                </td> --}}


                                {{-- Usage --}}
                                <td>

                                    <strong>
                                        {{ $coupon->used_count }}
                                    </strong>

                                    <span class="text-muted">
                                        /
                                        {{ $coupon->max_uses ?? '∞' }}
                                    </span>

                                </td>


                                {{-- Expiry --}}
                                <td>

                                    @if($coupon->expires_at)

                                        @if($coupon->expires_at->isPast())

                                            <span class="text-danger">
                                                <i class="fas fa-calendar-times me-1"></i>
                                                Expired
                                            </span>

                                            <br>

                                            <small class="text-muted">
                                                {{ $coupon->expires_at->format('d M Y, h:i A') }}
                                            </small>

                                        @else

                                            <span class="text-success">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                {{ $coupon->expires_at->format('d M Y') }}
                                            </span>

                                            <br>

                                            <small class="text-muted">
                                                {{ $coupon->expires_at->format('h:i A') }}
                                            </small>

                                        @endif

                                    @else

                                        <span class="text-muted">
                                            No expiry
                                        </span>

                                    @endif

                                </td>


                                {{-- Status --}}
                                <td>

                                    @if($coupon->isValid())

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    @elseif(!$coupon->is_active)

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    @elseif(
                                        $coupon->expires_at &&
                                        $coupon->expires_at->isPast()
                                    )

                                        <span class="badge bg-danger">
                                            Expired
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            Limit Reached
                                        </span>

                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td class="text-end pe-4">

                                    <div class="btn-group gap-2">

                                        {{-- Toggle --}}
                                        {{-- <form method="POST"
                                              action="{{ route('admin.coupons.toggle-status', $coupon) }}">

                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-{{ $coupon->is_active ? 'warning' : 'success' }}"
                                                    title="{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}">

                                                <i class="fas fa-{{ $coupon->is_active ? 'pause' : 'play' }}"></i>

                                            </button>

                                        </form> --}}


                                        {{-- Edit --}}
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit">

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        {{-- Delete --}}
                                        <form method="POST"
                                            action="{{ route('admin.coupons.destroy', $coupon) }}"
                                            class="delete-coupon-form d-inline">

                                            @csrf
                                            @method('DELETE')

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger delete-coupon-btn"
                                                    data-coupon="{{ $coupon->code }}"
                                                    title="Delete">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center py-5">

                                    <div class="text-muted">

                                        <h6>
                                            No coupons found
                                        </h6>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Pagination --}}
        @if($coupons->hasPages())

            <div class="card-footer bg-white">

                {{ $coupons->links() }}

            </div>

        @endif

    </div>

</div>



{{-- Delete Confirmation Modal --}}
<div class="modal fade"
     id="deleteCouponModal"
     tabindex="-1"
     aria-labelledby="deleteCouponModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4">

            {{-- Icon --}}
            <div class="modal-body text-center p-4 p-md-5">

                <div class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-danger-subtle"
                     style="width: 80px; height: 80px;">

                    <i class="fas fa-trash-alt text-danger"
                       style="font-size: 32px;">
                    </i>

                </div>

                <h4 class="fw-bold mb-2">
                    Delete Coupon?
                </h4>

                <p class="text-muted mb-3">
                    Are you sure you want to delete this coupon?
                </p>

                <div class=" bg-warning text-dark rounded-3 py-3 px-4 mb-4">

                    <div class="small mb-1">
                        Coupon Code
                    </div>

                    <div class="fw-bold"
                         id="deleteCouponCode">
                        -
                    </div>

                </div>

                <div class="alert alert-warning border-0 small text-start mb-4">

                    <i class="fas fa-exclamation-triangle me-2"></i>

                    This action cannot be undone. All coupon information
                    will be permanently deleted.

                </div>

                <div class="d-flex justify-content-center gap-2">

                    <button type="button"
                            class="btn btn-light border px-4"
                            data-bs-dismiss="modal">

                        <i class="fas fa-times me-1"></i>
                        Cancel

                    </button>

                    <button type="button"
                            class="btn btn-danger px-4"
                            id="confirmDeleteCoupon">

                        <i class="fas fa-trash-alt me-1"></i>
                        Yes, Delete

                    </button>

                </div>

            </div>

        </div>

    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function () {

    let deleteForm = null;

    const deleteModalElement =
        document.getElementById('deleteCouponModal');

    const deleteModal =
        new bootstrap.Modal(deleteModalElement);

    const couponCodeElement =
        document.getElementById('deleteCouponCode');

    const confirmDeleteButton =
        document.getElementById('confirmDeleteCoupon');


    /*
    |--------------------------------------------------------------------------
    | Open Delete Confirmation Modal
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll('.delete-coupon-btn').forEach(function (button) {

        button.addEventListener('click', function () {

            deleteForm =
                this.closest('.delete-coupon-form');

            const couponCode =
                this.dataset.coupon || '-';

            couponCodeElement.textContent =
                couponCode;

            deleteModal.show();

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Confirm Delete
    |--------------------------------------------------------------------------
    */

    confirmDeleteButton.addEventListener('click', function () {

        if (!deleteForm) {
            return;
        }

        const button = this;

        // Prevent multiple clicks
        button.disabled = true;

        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"
                  role="status"
                  aria-hidden="true">
            </span>
            Deleting...
        `;

        deleteForm.submit();

    });

});
</script>


@endsection
