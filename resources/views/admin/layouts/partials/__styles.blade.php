   <!-- Vendors styles-->
    <link rel="stylesheet" href="{{ asset('assets/vendors/simplebar/css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/vendors/simplebar.css') }}">
    <!-- Main styles for this application-->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <!-- We use those styles to show code examples, you should remove them in your application.-->
    <link href="{{ asset('assets/css/examples.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('assets/js/color-modes.js') }}"></script>
    <link href="{{ asset('assets/vendors/@coreui/chartjs/css/coreui-chartjs.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>

   <style>
        .custom-badge{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 12px;
            border-radius:30px;
            font-size:11px;
            font-weight:700;
            letter-spacing:.5px;
            width:fit-content;
            text-transform:uppercase;
        }

        .badge-dot{
            width:7px;
            height:7px;
            border-radius:50%;
        }

        .badge-credit{
            color:#00ff9d;
        }

        .badge-credit .badge-dot{
            background:#00ff9d;
            box-shadow:0 0 10px #00ff9d;
        }

        .badge-debit{
            color:#ff5a5a;
        }

        .badge-debit .badge-dot{
            background:#ff5a5a;
            box-shadow:0 0 10px #ff5a5a;
        }

        .badge-approved{
            color:#00ff9d;
        }

        .badge-pending{
            color:#ffc107;
        }

        .badge-failed{
            color:#ff5a5a;
        }

        .transaction-table td{
            vertical-align: middle;
        }

        .amount-positive{
            color:#00ff9d;
            font-weight:700;
        }

        .amount-negative{
            color:#ff5a5a;
            font-weight:700;
        }

        .user-name{
            font-weight:600;
        }

        .user-email{
            font-size:12px;
            color:#8c8c8c;
        }

        .method-title{
            font-weight:600;
        }

        .date-text{
            font-size:12px;
            color:#8c8c8c;
        }

    .user-avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #5b5bd6;
        color: #fff;
        font-size: 30px;
        font-weight: 600;
    }

    .info-box {
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 6px;
        padding: 12px 14px;
        min-height: 65px;
    }

    .info-label {
        font-size: 11px;
        color: #8d98a7;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 13px;
        font-weight: 500;
        color: #fff;
    }

    .wallet-address {
        font-size: 11px;
        word-break: break-all;
        line-height: 1.5;
    }

    .wallet-address-small {
        font-size: 11px;
        word-break: break-all;
        line-height: 1.6;
    }

    .balance-card {
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 8px;
        padding: 25px 20px;
        text-align: center;
    }

    .balance-label {
        font-size: 12px;
        color: #8d98a7;
        margin-bottom: 8px;
    }

    .balance-value {
        font-size: 30px;
        font-weight: 600;
        color: #fff;
    }

    .balance-value span {
        font-size: 16px;
        font-weight: 500;
        color: #8d98a7;
    }

    .wallet-box {
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 6px;
        padding: 14px;
    }

    @media (max-width: 991px) {

        .balance-value {
            font-size: 25px;
        }

    }

</style>

    @stack('auth_styles')
