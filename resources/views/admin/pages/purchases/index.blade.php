@extends('admin.layouts.app')

@section('title', 'Purchase History')

@section('content')

<div class="container">

    <div class="row">
        <div class="col-12">

            <div class="card mb-4">

                <div class="card-header fw-bold">
                    Purchase History
                </div>

                @include('admin.components.alerts')

                <div class="card-body">

                    <form
                        method="GET"
                        action="{{ route('admin.purchases.index') }}"
                    >

                        <div class="row g-2 mb-4">

                            {{-- Search --}}
                            <div class="col-md-6">

                                <input
                                    type="search"
                                    class="form-control"
                                    name="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search name, email, wallet, invoice or TX hash..."
                                >

                            </div>


                            {{-- Status Filter --}}
                            <div class="col-md-3">

                                <select name="status" class="form-select" >

                                    <option value="">  All Status </option>

                                    <option value="pending" @selected(request('status') === 'pending') >
                                        Pending
                                    </option>

                                    <option value="completed" @selected(request('status') === 'completed') >
                                        Completed
                                    </option>

                                    {{-- <option value="failed" @selected(request('status') === 'failed')>
                                        Failed
                                    </option> --}}

                                    <option value="cancelled" @selected(request('status') === 'cancelled') >
                                        Cancelled
                                    </option>

                                </select>

                            </div>


                            {{-- Search Button --}}
                            <div class="col-md-2 d-flex gap-2">

                                <button
                                    type="submit"
                                    class="btn btn-primary flex-grow-1"
                                >
                                    <i class="fas fa-search me-1"></i>
                                    Search
                                </button>


                                @if(
                                    request()->filled('search')
                                    || request()->filled('status')
                                )

                                    <a
                                        href="{{ route('admin.purchases.index') }}"
                                        class="btn btn-secondary"
                                        title="Reset"
                                    >
                                        <i class="fas fa-redo"></i>
                                    </a>

                                @endif

                            </div>

                        </div>

                    </form>


                    {{-- ================================================= --}}
                    {{-- TABLE --}}
                    {{-- ================================================= --}}

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle mb-0">

                            <thead>

                                <tr class="text-center">

                                    <th>#</th>

                                    <th>User</th>

                                    <th>Wallet Address</th>

                                    <th>Invoice ID</th>

                                    <th>USDT</th>

                                    <th>MIND</th>

                                    <th>Status</th>

                                    <th>Purchased At</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>


                            <tbody>

                            @forelse($purchases as $purchase)

                                <tr>

                                    {{-- ================================================= --}}
                                    {{-- SERIAL --}}
                                    {{-- ================================================= --}}

                                    <td class="text-center">
                                        {{ $purchases->firstItem() + $loop->index }}
                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- USER --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        <div class="fw-semibold text-nowrap">
                                            {{ $purchase->user->name ?: 'N/A' }}
                                        </div>

                                        @if($purchase->user->email)

                                            <small class="text-muted text-nowrap">
                                                {{ $purchase->user->email }}
                                            </small>

                                        @endif

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- WALLET --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        @php

                                            $wallet =
                                                $purchase->user->wallet_address ?? '';

                                            $shortWallet = strlen($wallet) > 12
                                                ? substr($wallet, 0, 6)
                                                    . '...'
                                                    . substr($wallet, -6)
                                                : $wallet;

                                        @endphp


                                        @if($wallet)

                                            <div class="d-flex align-items-center gap-2">

                                                <span
                                                    title="{{ $wallet }}"
                                                    class="text-nowrap"
                                                    style="font-family: monospace;"
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


                                    {{-- ================================================= --}}
                                    {{-- INVOICE --}}
                                    {{-- ================================================= --}}

                                    <td>

                                        @if($purchase->invoice_id)

                                            <span
                                                class="text-nowrap"
                                                title="{{ $purchase->invoice_id }}"
                                            >
                                                {{ \Illuminate\Support\Str::limit(
                                                    $purchase->invoice_id,
                                                    18
                                                ) }}
                                            </span>

                                        @else

                                            <span class="text-muted">
                                                N/A
                                            </span>

                                        @endif

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- USDT --}}
                                    {{-- ================================================= --}}

                                    <td class="text-end text-nowrap">

                                        <strong>
                                            {{ number_format(
                                                (float) (
                                                    $purchase->received_usdt
                                                    ?: $purchase->payable_usdt
                                                ),
                                                3
                                            ) }}
                                        </strong>

                                        <small class="text-muted">
                                            USDT
                                        </small>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- MIND --}}
                                    {{-- ================================================= --}}

                                    <td class="text-end text-nowrap">

                                        <strong>
                                            {{ number_format(
                                                (float) $purchase->total_mind,
                                                3
                                            ) }}
                                        </strong>

                                        <small class="text-muted">
                                            MIND
                                        </small>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- STATUS --}}
                                    {{-- ================================================= --}}

                                    <td class="text-center">

                                        @php

                                            $statusClass = match($purchase->status) {

                                                'completed' => 'bg-success',

                                                'pending' => 'bg-warning text-dark',

                                                'failed' => 'bg-danger',

                                                'cancelled' => 'bg-secondary',

                                                default => 'bg-secondary',

                                            };

                                        @endphp


                                        <span class="badge {{ $statusClass }}">

                                            {{ ucfirst($purchase->status) }}

                                        </span>

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- DATE --}}
                                    {{-- ================================================= --}}

                                    <td class="text-nowrap">

                                        @if($purchase->created_at)

                                            <span
                                                class="local-datetime"
                                                data-datetime="{{ $purchase->created_at->utc()->toIso8601String() }}"
                                            >
                                                Loading...
                                            </span>

                                        @else

                                            N/A

                                        @endif

                                    </td>


                                    {{-- ================================================= --}}
                                    {{-- ACTIONS --}}
                                    {{-- ================================================= --}}

                                    <td class="text-center">

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#purchaseViewModal{{ $purchase->id }}"
                                            title="View Purchase"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>

                                    </td>

                                </tr>

                            @include('admin.pages.purchases.models.showModel', ['purchase' => $purchase])

                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="text-center py-5"
                                    >

                                        <div class="text-muted">

                                            <i class="fas fa-receipt fa-2x mb-2"></i>

                                            <div>
                                                No purchase history found.
                                            </div>

                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ================================================= --}}
                    {{-- PAGINATION --}}
                    {{-- ================================================= --}}

                    @if($purchases->hasPages())

                        <div class="mt-3">

                            {{ $purchases->links(
                                'admin.components.pagination'
                            ) }}

                        </div>

                    @endif

                </div>

            </div>

        </div>
    </div>
</div>

@endsection
