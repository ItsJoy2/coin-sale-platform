@extends('admin.layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="container-lg px-4">

<div class="row g-4 mb-4">

    {{-- Total MIND Sold --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card text-white bg-primary h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <div class="fs-2 fw-bold">
                        {{ number_format($DashboardData['sales']['total_mind_sold'] ?? 0, 3) }}
                    </div>

                    <div class="opacity-75">
                        Total MIND Sold
                    </div>
                </div>

                <div class="display-5 opacity-50">
                    <i class="fas fa-coins"></i>
                </div>

            </div>
        </div>
    </div>


    {{-- Total USDT --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card text-white bg-success h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <div class="fs-2 fw-bold">
                        ${{ number_format($DashboardData['sales']['total_usdt_sold'] ?? 0, 2) }}
                    </div>

                    <div class="opacity-75">
                        Total USDT Received
                    </div>
                </div>

                <div class="display-5 opacity-50">
                    <i class="fas fa-dollar-sign"></i>
                </div>

            </div>
        </div>
    </div>


    {{-- Total Purchases --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card text-white bg-info h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <div class="fs-2 fw-bold">
                        {{ number_format($DashboardData['sales']['total_purchases'] ?? 0) }}
                    </div>

                    <div class="opacity-75">
                        Total Purchases
                    </div>
                </div>

                <div class="display-5 opacity-50">
                    <i class="fas fa-shopping-cart"></i>
                </div>

            </div>
        </div>
    </div>


    {{-- Total Buyers --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <div class="fs-2 fw-bold">
                        {{ number_format($DashboardData['sales']['total_buyers'] ?? 0) }}
                    </div>

                    <div class="opacity-75">
                        Total Buyers
                    </div>
                </div>

                <div class="display-5 opacity-50">
                    <i class="fas fa-users"></i>
                </div>

            </div>
        </div>
    </div>

</div>


{{-- ========================================================= --}}
{{-- MIND BREAKDOWN --}}
{{-- ========================================================= --}}

<div class="row g-4 mb-4">

    {{-- Main MIND --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body">

                <small class="text-body-secondary">
                    Main MIND Sold
                </small>

                <h4 class="mt-2 mb-0 fw-bold text-primary">
                    {{ number_format($DashboardData['sales']['total_main_mind'] ?? 0, 3) }}
                </h4>

            </div>
        </div>
    </div>


    {{-- Bonus MIND --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body">

                <small class="text-body-secondary">
                    Bonus MIND
                </small>

                <h4 class="mt-2 mb-0 fw-bold text-warning">
                    {{ number_format($DashboardData['sales']['total_bonus_mind'] ?? 0, 3) }}
                </h4>

            </div>
        </div>
    </div>


    {{-- Average Purchase --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body">

                <small class="text-body-secondary">
                    Average Purchase
                </small>

                <h4 class="mt-2 mb-0 fw-bold text-success">
                    ${{ number_format($DashboardData['sales']['average_purchase_usdt'] ?? 0, 2) }}
                </h4>

            </div>
        </div>
    </div>


    {{-- Average MIND --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body">

                <small class="text-body-secondary">
                    Avg. MIND / Purchase
                </small>

                <h4 class="mt-2 mb-0 fw-bold text-info">
                    {{ number_format($DashboardData['sales']['average_mind_per_purchase'] ?? 0, 3) }}
                </h4>

            </div>
        </div>
    </div>

</div>


{{-- ========================================================= --}}
{{-- TODAY / THIS MONTH --}}
{{-- ========================================================= --}}

<div class="row g-4 mb-4">

    {{-- TODAY --}}
    <div class="col-lg-6">

        <div class="card h-100">

            <div class="card-header">
                <strong>
                    <i class="fas fa-calendar-day me-2"></i>
                    Today's Sales
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            Purchases
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ number_format($DashboardData['today']['purchases'] ?? 0) }}
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            USDT
                        </div>

                        <div class="fs-4 fw-bold text-success">
                            ${{ number_format($DashboardData['today']['usdt'] ?? 0, 2) }}
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            MIND
                        </div>

                        <div class="fs-4 fw-bold text-primary">
                            {{ number_format($DashboardData['today']['mind'] ?? 0, 3) }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- THIS MONTH --}}
    <div class="col-lg-6">

        <div class="card h-100">

            <div class="card-header">
                <strong>
                    <i class="fas fa-calendar-alt me-2"></i>
                    This Month's Sales
                </strong>
            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            Purchases
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ number_format($DashboardData['this_month']['purchases'] ?? 0) }}
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            USDT
                        </div>

                        <div class="fs-4 fw-bold text-success">
                            ${{ number_format($DashboardData['this_month']['usdt'] ?? 0, 2) }}
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-body-secondary small">
                            MIND
                        </div>

                        <div class="fs-4 fw-bold text-primary">
                            {{ number_format($DashboardData['this_month']['mind'] ?? 0, 3) }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- ========================================================= --}}
{{-- TOTAL USER + REFERRAL + PENDING --}}
{{-- ========================================================= --}}

<div class="row g-4 mb-4">

    {{-- Total Users --}}
    <div class="col-sm-6 col-xl-4">

        <div class="card border-start border-primary border-4 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-body-secondary">
                            Total Users
                        </small>

                        <h4 class="mt-2 mb-0 fw-bold text-primary">
                            {{ number_format($DashboardData['totalUsers'] ?? 0) }}
                        </h4>

                    </div>

                    <div class="display-5 text-primary opacity-50">
                        <i class="fas fa-users"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Total Referral Bonus --}}
    <div class="col-sm-6 col-xl-4">

        <div class="card border-start border-warning border-4 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-body-secondary">
                            Total Referral Bonus
                        </small>

                        <h4 class="mt-2 mb-0 fw-bold text-warning">
                            {{ number_format($DashboardData['referral']['total_referral_bonus_mind'] ?? 0, 3) }}
                            MIND
                        </h4>

                        <small class="text-body-secondary">
                            Value:
                            ${{ number_format($DashboardData['referral']['total_referral_bonus_usdt'] ?? 0, 2) }}
                        </small>

                    </div>

                    <div class="display-5 text-warning opacity-50">
                        <i class="fas fa-user-plus"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Pending Purchases --}}
    <div class="col-sm-6 col-xl-4">

        <div class="card border-start border-danger border-4 h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <small class="text-body-secondary">
                            Pending Purchases
                        </small>

                        <h4 class="mt-2 mb-0 fw-bold text-danger">
                            {{ number_format($DashboardData['pending']['pending_purchases'] ?? 0) }}
                        </h4>

                    </div>

                    <div class="display-5 text-danger opacity-50">
                        <i class="fas fa-clock"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



{{-- ========================================================= --}}
{{-- LATEST PURCHASES --}}
{{-- ========================================================= --}}


<div class="card mb-4">

    <div class="card-header d-flex justify-content-between align-items-center">

        <strong>
            <i class="fas fa-history me-2"></i>
            Latest Purchases
        </strong>

        <span class="badge bg-success">
            Completed
        </span>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Wallet</th>
                        <th>USDT</th>
                        <th>Main MIND</th>
                        <th>Bonus</th>
                        <th>Total MIND</th>
                        <th>Rate</th>
                        <th>TX Hash</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($DashboardData['latest_purchases'] ?? [] as $index => $purchase)

                        <tr>

                            {{-- Serial Number --}}
                            <td>
                                {{ $index + 1 }}
                            </td>

                            {{-- Wallet --}}
                            <td>

                                @if(!empty($purchase['user']['wallet_address']))

                                    <span
                                        title="{{ $purchase['user']['wallet_address'] }}"
                                        style="cursor:pointer;"
                                    >
                                        {{ \Illuminate\Support\Str::limit(
                                            $purchase['user']['wallet_address'],
                                            16
                                        ) }}
                                    </span>

                                @else
                                    N/A
                                @endif

                            </td>

                            {{-- USDT --}}
                            <td class="fw-bold text-success">
                                ${{ number_format($purchase['received_usdt'] ?? 0, 2) }}
                            </td>

                            {{-- Main MIND --}}
                            <td>
                                {{ number_format($purchase['mind_amount'] ?? 0, 3) }}
                            </td>

                            {{-- Bonus --}}
                            <td class="text-warning">
                                {{ number_format($purchase['bonus_mind'] ?? 0, 3) }}
                            </td>

                            {{-- Total MIND --}}
                            <td class="fw-bold text-primary">
                                {{ number_format($purchase['total_mind'] ?? 0, 3) }}
                            </td>

                            {{-- Rate --}}
                            <td>
                                ${{ number_format($purchase['mind_price'] ?? 0, 4) }}
                            </td>

                            {{-- TX Hash --}}
                            <td>

                                @if(!empty($purchase['tx_hash']))

                                    <span
                                        title="{{ $purchase['tx_hash'] }}"
                                        style="cursor:pointer;"
                                    >
                                        {{ \Illuminate\Support\Str::limit(
                                            $purchase['tx_hash'],
                                            14
                                        ) }}
                                    </span>

                                @else
                                    N/A
                                @endif

                            </td>

                            {{-- Date --}}
                            <td>
                                @if(!empty($purchase['completed_at']))
                                    <span
                                        class="local-datetime"
                                        data-datetime="{{ \Carbon\Carbon::parse($purchase['completed_at'])->utc()->toIso8601String() }}"
                                    >
                                        Loading...
                                    </span>
                                @else
                                    N/A
                                @endif
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="9" class="text-center py-4 text-body-secondary">
                                No completed purchases found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>



</div>

@endsection


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.local-datetime').forEach(function (element) {

                const dateString = element.dataset.datetime;

                if (!dateString) {
                    return;
                }

                const date = new Date(dateString);

                if (isNaN(date.getTime())) {
                    element.textContent = 'N/A';
                    return;
                }

                element.textContent = new Intl.DateTimeFormat(
                    undefined,
                    {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }
                ).format(date);

            });

        });
    </script>

