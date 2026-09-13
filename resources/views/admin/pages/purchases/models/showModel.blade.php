
                                {{-- ================================================= --}}
                                {{-- PURCHASE VIEW MODAL --}}
                                {{-- ================================================= --}}

                                <div class="modal fade" id="purchaseViewModal{{ $purchase->id }}" tabindex="-1" aria-hidden="true" >

                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

                                        <div class="modal-content">

                                            {{-- Modal Header --}}
                                            <div class="modal-header">

                                                <h5 class="modal-title">

                                                    <i class="fas fa-receipt me-2"></i>

                                                    Purchase Details

                                                    <span class="text-muted">
                                                        #{{ $purchase->id }}
                                                    </span>

                                                </h5>


                                                <button
                                                    type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal"
                                                ></button>

                                            </div>


                                            {{-- Modal Body --}}
                                            <div class="modal-body">

                                                {{-- ================================================= --}}
                                                {{-- USER INFORMATION --}}
                                                {{-- ================================================= --}}

                                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                                    <i class="fas fa-user me-1"></i>
                                                    User Information
                                                </h6>


                                                <div class="row g-3 mb-4">

                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Name
                                                        </div>

                                                        <div class="fw-semibold">
                                                            {{ $purchase->user->name ?: 'N/A' }}
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Email
                                                        </div>

                                                        <div>
                                                            {{ $purchase->user->email ?: 'N/A' }}
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Wallet Address
                                                        </div>

                                                        {{-- <div
                                                            class="text-break"
                                                            style="font-family: monospace; font-size: 13px;"
                                                        >
                                                            {{ $purchase->user->wallet_address ?: 'N/A' }}
                                                        </div> --}}

                                                        <div class="d-flex align-items-center gap-2">

                                                            <div
                                                                class="info-value"
                                                                id="referralWallet"
                                                                data-wallet="{{ $purchase->user->wallet_address }}"
                                                                title="{{ $purchase->user->wallet_address }}"
                                                                style="font-size: 11px;"
                                                            >
                                                                {{ $purchase->user->wallet_address ?: 'N/A' }}
                                                            </div>

                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-secondary copy-wallet"
                                                                data-wallet="{{ $purchase->user->wallet_address }}"
                                                                title="Copy wallet address"
                                                            >
                                                                <i class="fas fa-copy"></i>
                                                            </button>

                                                        </div>

                                                    </div>

                                                </div>


                                                {{-- ================================================= --}}
                                                {{-- PURCHASE INFORMATION --}}
                                                {{-- ================================================= --}}

                                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                                    <i class="fas fa-shopping-cart me-1"></i>
                                                    Purchase Information
                                                </h6>


                                                <div class="row g-3 mb-4">

                                                    {{-- <div class="col-md-3">

                                                        <div class="small text-muted">
                                                            Purchase ID
                                                        </div>

                                                        <div class="fw-semibold">
                                                            {{ $purchase->id }}
                                                        </div>

                                                    </div> --}}


                                                    <div class="col-md-3">

                                                        <div class="small text-muted">
                                                            Invoice ID
                                                        </div>

                                                        <div class="text-break">
                                                            {{ $purchase->invoice_id ?: 'N/A' }}
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Payment Address
                                                        </div>

                                                        {{-- <div
                                                            class="text-break"
                                                            style="font-family: monospace; font-size: 13px;"
                                                        >
                                                            {{ $purchase->payment_address ?: 'N/A' }}
                                                        </div> --}}

                                                        <div class="d-flex align-items-center gap-2">

                                                            <div
                                                                class="info-value"
                                                                id="referralWallet"
                                                                data-wallet="{{ $purchase->payment_address }}"
                                                                title="{{ $purchase->payment_address }}"
                                                                style="font-size: 11px;"
                                                            >
                                                                {{ $purchase->payment_address ?: 'N/A' }}
                                                            </div>

                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-secondary copy-wallet"
                                                                data-wallet="{{ $purchase->payment_address }}"
                                                                title="Copy wallet address"
                                                            >
                                                                <i class="fas fa-copy"></i>
                                                            </button>

                                                        </div>
                                                    </div>


                                                    <div class="col-md-2">

                                                        <div class="small text-muted">
                                                            Coupon Code
                                                        </div>

                                                        <div>
                                                            {{ $purchase->coupon_code ?: 'N/A' }}
                                                        </div>

                                                    </div>


                                                    <div class="col-md-3">

                                                        <div class="small text-muted">
                                                            Bonus Percentage
                                                        </div>

                                                        <div>
                                                            {{ number_format((float) $purchase->bonus_percentage, 2) }}%
                                                        </div>

                                                    </div>

                                                </div>


                                                {{-- ================================================= --}}
                                                {{-- PAYMENT / TOKEN DETAILS --}}
                                                {{-- ================================================= --}}

                                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                                    <i class="fas fa-coins me-1"></i>
                                                    Payment & Token Details
                                                </h6>


                                                <div class="row g-3 mb-4">

                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Original USDT
                                                        </div>

                                                        <div class="fw-semibold">
                                                            {{ number_format((float) $purchase->usdt_amount, 8) }}
                                                            USDT
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Payable USDT
                                                        </div>

                                                        <div class="fw-semibold">
                                                            {{ number_format((float) $purchase->payable_usdt, 8) }}
                                                            USDT
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Received USDT
                                                        </div>

                                                        <div class="fw-semibold">
                                                            {{ number_format((float) $purchase->received_usdt, 8) }}
                                                            USDT
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            MIND Price
                                                        </div>

                                                        <div>
                                                            {{ number_format((float) $purchase->mind_price, 8) }}
                                                            USDT
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Main MIND
                                                        </div>

                                                        <div>
                                                            {{ number_format((float) $purchase->mind_amount, 8) }}
                                                            MIND
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Bonus MIND
                                                        </div>

                                                        <div>
                                                            {{ number_format((float) $purchase->bonus_mind, 8) }}
                                                            MIND
                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Total MIND
                                                        </div>

                                                        <div class="fw-bold text-success">
                                                            {{ number_format((float) $purchase->total_mind, 8) }}
                                                            MIND
                                                        </div>

                                                    </div>

                                                </div>


                                                {{-- ================================================= --}}
                                                {{-- TRANSACTION --}}
                                                {{-- ================================================= --}}

                                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                                    <i class="fas fa-link me-1"></i>
                                                    Transaction Information
                                                </h6>


                                                <div class="row g-3 mb-4">

                                                    <div class="col-12">

                                                        <div class="small text-muted">
                                                            Transaction Hash
                                                        </div>

                                                        {{-- <div
                                                            class="text-break"
                                                            style="font-family: monospace; font-size: 13px;"
                                                        >
                                                            {{ $purchase->tx_hash ?: 'N/A' }}
                                                        </div> --}}

                                                        <div class="d-flex align-items-center gap-2">

                                                            <div
                                                                class="info-value text-warning"
                                                                id="referralWallet"
                                                                data-wallet="{{ $purchase->tx_hash }}"
                                                                title="{{ $purchase->tx_hash }}"
                                                                style="font-size: 11px;"
                                                            >
                                                                {{ $purchase->tx_hash ?: 'N/A' }}
                                                            </div>

                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-outline-secondary copy-wallet"
                                                                data-wallet="{{ $purchase->tx_hash }}"
                                                                title="Copy wallet address"
                                                            >
                                                                <i class="fas fa-copy"></i>
                                                            </button>

                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Status
                                                        </div>

                                                        <div>

                                                            <span class="badge {{ $statusClass }}">

                                                                {{ ucfirst($purchase->status) }}

                                                            </span>

                                                        </div>

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Paid At
                                                        </div>

                                                        @if($purchase->paid_at)

                                                            <span
                                                                class="local-datetime"
                                                                data-datetime="{{ $purchase->paid_at->utc()->toIso8601String() }}"
                                                            >
                                                                Loading...
                                                            </span>

                                                        @else

                                                            N/A

                                                        @endif

                                                    </div>


                                                    <div class="col-md-4">

                                                        <div class="small text-muted">
                                                            Completed At
                                                        </div>

                                                        @if($purchase->completed_at)

                                                            <span
                                                                class="local-datetime"
                                                                data-datetime="{{ $purchase->completed_at->utc()->toIso8601String() }}"
                                                            >
                                                                Loading...
                                                            </span>

                                                        @else

                                                            N/A

                                                        @endif

                                                    </div>


                                                    <div class="col-12">

                                                        <div class="small text-muted">
                                                            Failure Reason
                                                        </div>

                                                        <div>
                                                            {{ $purchase->failure_reason ?: 'N/A' }}
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>


                                            {{-- ================================================= --}}
                                            {{-- MODAL FOOTER --}}
                                            {{-- ================================================= --}}

                                            <div class="modal-footer">

                                                {{-- Status Update --}}
                                                <div class="d-flex align-items-center gap-2">

                                                    <label
                                                        for="statusSelect{{ $purchase->id }}"
                                                        class="mb-0 fw-semibold"
                                                    >
                                                        Status:
                                                    </label>

                                                    <select id="statusSelect{{ $purchase->id }}" class="form-select" style="width: 160px;"  {{ $purchase->status === 'completed' ? 'disabled' : '' }}>

                                                        <option
                                                            value="pending"
                                                            @selected($purchase->status === 'pending')
                                                        >
                                                            Pending
                                                        </option>

                                                        <option
                                                            value="completed"
                                                            @selected($purchase->status === 'completed')
                                                        >
                                                            Completed
                                                        </option>

                                                        {{-- <option
                                                            value="failed"
                                                            @selected($purchase->status === 'failed')
                                                        >
                                                            Failed
                                                        </option> --}}

                                                        <option
                                                            value="cancelled"
                                                            @selected($purchase->status === 'cancelled')
                                                        >
                                                            Cancelled
                                                        </option>

                                                    </select>

                                                    <button type="button" class="btn btn-primary" onclick="handlePurchaseStatusChange( {{ $purchase->id }}, '{{ $purchase->status }}' )" {{ $purchase->status === 'completed' ? 'disabled' : '' }} >
                                                        <i class="fas fa-save me-1"></i>
                                                        Update
                                                    </button>

                                                </div>


                                                {{-- Direct status update form --}}
                                                {{-- Used only for pending -> failed/cancelled --}}
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.purchases.status', $purchase->id) }}"
                                                    id="directStatusForm{{ $purchase->id }}"
                                                    class="d-none"
                                                >

                                                    @csrf
                                                    @method('PUT')

                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value=""
                                                    >

                                                </form>


                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal"
                                                >
                                                    Close
                                                </button>

                                            </div>
@include('admin.pages.purchases.models.payment_verification')
                                        </div>

                                    </div>

                                </div>


<script>

function handlePurchaseStatusChange(purchaseId, currentStatus)
{
    const select = document.getElementById(
        'statusSelect' + purchaseId
    );

    if (!select) {
        return;
    }

    const newStatus = select.value;

    /*
    |--------------------------------------------------------------------------
    | No change
    |--------------------------------------------------------------------------
    */
    if (newStatus === currentStatus) {

        alert('No status change detected.');

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Completed purchase cannot be changed
    |--------------------------------------------------------------------------
    */
    if (currentStatus === 'completed') {

        alert(
            'A completed purchase cannot be changed to another status.'
        );

        select.value = currentStatus;

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Pending -> Completed
    |--------------------------------------------------------------------------
    |
    | Open payment verification modal.
    |
    */
    if (
        currentStatus === 'pending' &&
        newStatus === 'completed'
    ) {

        const verifyModalElement =
            document.getElementById(
                'paymentVerifyModal' + purchaseId
            );

        if (!verifyModalElement) {

            alert(
                'Payment verification modal not found.'
            );

            return;
        }

        const verifyModal =
            bootstrap.Modal.getOrCreateInstance(
                verifyModalElement
            );

        verifyModal.show();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Pending -> Failed / Cancelled
    |--------------------------------------------------------------------------
    */
    const form = document.getElementById(
        'directStatusForm' + purchaseId
    );

    if (!form) {

        alert(
            'Status update form not found.'
        );

        return;
    }

    form.querySelector(
        'input[name="status"]'
    ).value = newStatus;

    form.submit();
}


/*
|--------------------------------------------------------------------------
| Verify Payment
|--------------------------------------------------------------------------
*/
function verifyPayment(purchaseId)
{
    const receivedInput = document.getElementById(
        'receivedUsdt' + purchaseId
    );

    const errorElement = document.getElementById(
        'receivedUsdtError' + purchaseId
    );

    if (!receivedInput || !errorElement) {
        return;
    }

    const received = receivedInput.value.trim();


    /*
    |--------------------------------------------------------------------------
    | Get payable amount
    |--------------------------------------------------------------------------
    */
    const payableInput = document.querySelector(
        '#paymentVerifyModal' + purchaseId +
        ' input[name="payable_usdt"]'
    );


    /*
    |--------------------------------------------------------------------------
    | Fallback: first readonly input
    |--------------------------------------------------------------------------
    */
    const payableElement = payableInput
        || document.querySelector(
            '#paymentVerifyModal' + purchaseId +
            ' input[readonly]'
        );

    if (!payableElement) {

        alert(
            'Payable USDT amount not found.'
        );

        return;
    }

    const payable = payableElement.value.trim();


    /*
    |--------------------------------------------------------------------------
    | Clear previous error
    |--------------------------------------------------------------------------
    */
    errorElement.textContent = '';
    errorElement.classList.add('d-none');


    /*
    |--------------------------------------------------------------------------
    | Empty amount
    |--------------------------------------------------------------------------
    */
    if (!received) {

        errorElement.textContent =
            'Please enter the received USDT amount.';

        errorElement.classList.remove('d-none');

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Numeric validation
    |--------------------------------------------------------------------------
    */
    if (
        isNaN(received) ||
        parseFloat(received) <= 0
    ) {

        errorElement.textContent =
            'Please enter a valid USDT amount.';

        errorElement.classList.remove('d-none');

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Exact 8 decimal comparison
    |--------------------------------------------------------------------------
    */
    const receivedFixed =
        parseFloat(received).toFixed(8);

    const payableFixed =
        parseFloat(payable).toFixed(8);


    if (receivedFixed !== payableFixed) {

        errorElement.textContent =
            'Received USDT must exactly match payable USDT: '
            + payableFixed
            + ' USDT.';

        errorElement.classList.remove('d-none');

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Set confirmation amount
    |--------------------------------------------------------------------------
    */
    const confirmAmountElement =
        document.getElementById(
            'confirmReceivedUsdt' + purchaseId
        );

    const confirmInput =
        document.getElementById(
            'confirmReceivedInput' + purchaseId
        );

    if (confirmAmountElement) {
        confirmAmountElement.textContent =
            receivedFixed + ' USDT';
    }

    if (confirmInput) {
        confirmInput.value = receivedFixed;
    }


    /*
    |--------------------------------------------------------------------------
    | Close verification modal
    |--------------------------------------------------------------------------
    */
    const verifyModalElement =
        document.getElementById(
            'paymentVerifyModal' + purchaseId
        );

    const verifyModal =
        bootstrap.Modal.getInstance(
            verifyModalElement
        );

    if (verifyModal) {
        verifyModal.hide();
    }


    /*
    |--------------------------------------------------------------------------
    | Open confirmation modal
    |--------------------------------------------------------------------------
    */
    setTimeout(function () {

        const confirmModalElement =
            document.getElementById(
                'completeConfirmModal' + purchaseId
            );

        if (!confirmModalElement) {

            alert(
                'Confirmation modal not found.'
            );

            return;
        }

        const confirmModal =
            bootstrap.Modal.getOrCreateInstance(
                confirmModalElement
            );

        confirmModal.show();

    }, 350);
}

</script>
