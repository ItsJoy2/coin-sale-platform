{{-- Payment Verification Modal --}}
<div
    class="modal fade"
    id="paymentVerifyModal{{ $purchase->id }}"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shield-alt me-1"></i>
                    Verify Payment
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>

                    Please verify the received USDT amount before completing
                    this purchase.
                </div>

                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label">
                            Payable USDT
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="{{ number_format((float) $purchase->payable_usdt, 8, '.', '') }}"
                            readonly
                        >
                    </div>

                    <div class="col-12">
                        <label class="form-label">
                            Received USDT
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            step="0.00000001"
                            min="0"
                            class="form-control received-usdt-input"
                            id="receivedUsdt{{ $purchase->id }}"
                            placeholder="Enter received USDT"
                        >

                        <div
                            class="text-danger small mt-1 d-none"
                            id="receivedUsdtError{{ $purchase->id }}"
                        ></div>
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="verifyPayment({{ $purchase->id }})"
                >
                    <i class="fas fa-check me-1"></i>
                    Verify & Continue
                </button>

            </div>

            {{-- @include('admin.pages.purchases.models.confirmation') --}}
        </div>
    </div>
</div>


{{-- Final Confirmation Modal --}}
<div
    class="modal fade"
    id="completeConfirmModal{{ $purchase->id }}"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-1"></i>
                    Confirm Purchase Completion
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>

                    <strong>Important:</strong>
                    Once confirmed, the purchase will be completed and
                    MIND will be credited to the user.
                </div>

                <div class="border rounded p-3">

                    <div class="d-flex justify-content-between mb-2">
                        <span>Payable USDT</span>
                        <strong>
                            {{ number_format((float) $purchase->payable_usdt, 8, '.', '') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Received USDT</span>
                        <strong
                            id="confirmReceivedUsdt{{ $purchase->id }}"
                        >
                            -
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Main MIND</span>
                        <strong>
                            {{ number_format((float) $purchase->mind_amount, 8, '.', '') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Bonus MIND</span>
                        <strong>
                            {{ number_format((float) $purchase->bonus_mind, 8, '.', '') }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Total MIND</span>
                        <strong class="text-success">
                            {{ number_format((float) $purchase->total_mind, 8, '.', '') }}
                        </strong>
                    </div>

                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        User:
                        {{ $purchase->user->wallet_address }}
                    </small>
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <form
                    method="POST"
                    action="{{ route('admin.purchases.status', $purchase->id) }}"
                    id="completePurchaseForm{{ $purchase->id }}"
                >
                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="status"
                        value="completed"
                    >

                    <input
                        type="hidden"
                        name="received_usdt"
                        id="confirmReceivedInput{{ $purchase->id }}"
                    >

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        <i class="fas fa-check me-1"></i>
                        Confirm & Complete
                    </button>
                </form>

            </div>

        </div>
    </div>
</div>
