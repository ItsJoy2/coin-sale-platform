
<div
    class="modal fade"
    id="balanceAdjustModal"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form
                action="{{ route('admin.users.balance.adjust', $user->id) }}"
                method="POST"
                id="balanceAdjustForm"
            >

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title" id="balanceModalTitle">
                        Add MIND
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>

                <div class="modal-body">

                    {{-- Current Balance --}}
                    <div class="alert alert-secondary">

                        <div class="small text-dark">
                            Current Balance
                        </div>

                        <strong>
                            {{ number_format((float) $user->mind_balance, 3) }}
                            MIND
                        </strong>

                    </div>


                    {{-- Action --}}
                    <input
                        type="hidden"
                        name="action"
                        id="balanceAction"
                        value="add"
                    >


                    {{-- Selected Action --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Adjustment Type
                        </label>

                        <div class="btn-group w-100" role="group">

                            <button
                                type="button"
                                class="btn btn-success"
                                id="addBalanceBtn"
                                onclick="setBalanceAction('add')"
                            >
                                <i class="fas fa-plus me-1"></i>
                                Add MIND
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-danger"
                                id="deductBalanceBtn"
                                onclick="setBalanceAction('deduct')"
                            >
                                <i class="fas fa-minus me-1"></i>
                                Deduct MIND
                            </button>

                        </div>

                    </div>


                    {{-- Amount --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Amount (MIND)
                        </label>

                        <input
                            type="number"
                            name="amount"
                            id="balanceAmount"
                            class="form-control"
                            min="0.00000001"
                            step="0.00000001"
                            placeholder="0.00000000"
                            required
                        >

                    </div>


                    {{-- Reason --}}
                    <div class="mb-3">

                        <label class="form-label">
                            Reason
                        </label>

                        <textarea
                            name="reason"
                            id="balanceReason"
                            class="form-control"
                            rows="3"
                            placeholder="Enter reason for this adjustment..."
                            required
                        ></textarea>

                    </div>


                    {{-- Warning --}}
                    <div
                        class="alert alert-warning d-none"
                        id="deductWarning"
                    >
                        <i class="fas fa-exclamation-triangle me-1"></i>

                        You are about to deduct MIND from this user's balance.
                    </div>


                    <div class="small text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        This action will be recorded in the transaction history.
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
                        type="submit"
                        class="btn btn-success"
                        id="balanceSubmitBtn"
                    >
                        <i class="fas fa-plus me-1"></i>
                        Add MIND
                    </button>

                </div>

            </form>

        </div>

    </div>
</div>


