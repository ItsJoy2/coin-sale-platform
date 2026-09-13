    <div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
      <div class="sidebar-header border-bottom">
        <div class="sidebar-brand me-auto">
            @php
                $siteLogo = \App\Models\Setting::where('key', 'logo')->value('value');
            @endphp

            <img src="{{ $siteLogo ? asset('storage/' . $siteLogo) : asset('assets/mindchsinwallet.png') }}" alt="Mindchain Logo" class="sidebar-brand-full" height="40" >
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
      </div>
      <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
        <li class="nav-item">
          <a class="nav-link" href=" {{ route('admin.dashboard') }}">
            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M425.706 142.294A240 240 0 0 0 16 312v88h144v-32H48v-56c0-114.691 93.309-208 208-208s208 93.309 208 208v56H352v32h144v-88a238.43 238.43 0 0 0-70.294-169.706" class="ci-primary" />
              <path fill="var(--ci-primary-color, currentcolor)" d="M80 264h32v32H80zm160-136h32v32h-32zm-104 40h32v32h-32zm264 96h32v32h-32zm-102.778 71.1 69.2-144.173-28.85-13.848-69.183 144.135a64.141 64.141 0 1 0 28.833 13.886M256 416a32 32 0 1 1 32-32 32.036 32.036 0 0 1-32 32" class="ci-primary" />
            </svg>
            Dashboard
            {{-- <span class="badge badge-sm bg-info ms-auto">NEW</span> --}}
          </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.users.index') }}">
                <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                    <path fill="var(--ci-primary-color, currentcolor)"
                        d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3zM528 256A112 112 0 1 0 528 32a112 112 0 1 0 0 224zm-16 48H480c-13.3 0-25.9 2.3-37.6 6.5c54.5 41.8 89.6 107.5 89.6 181.8c0 10.2-1.1 20.2-3.1 29.7H610.3c16.4 0 29.7-13.3 29.7-29.7C640 389.8 582.2 304 512 304z" />
                </svg>
                Users
            </a>
        </li>
                <li class="nav-group {{ request()->routeIs('admin.coupons.*') ? 'show' : '' }}">

            <a class="nav-link" href="{{ route('admin.coupons.index') }}">
                <i class="fas fa-ticket-alt nav-icon"></i>
                Coupons
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.purchases.index') }}">
                <svg class="nav-icon"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24">
                    <path fill="var(--ci-primary-color, currentcolor)"
                        d="M7 4h-2l-1 2v2h2l2.6 9.5A2 2 0 0 0 10.5 19H18v-2h-7.5a.5.5 0 0 1-.48-.37L9.7 15H18a2 2 0 0 0 1.9-1.37L22 7H7.21l-.66-3H7V4zm3 17a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm8 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>
                </svg>
                Purchases
            </a>
        </li>
        <li class="nav-group {{ request()->routeIs('admin.transactions.*') || request()->routeIs('admin.ambassador-history.*') ? 'show' : '' }}">

            <a class="nav-link " href="{{ route('admin.transactions.index') }}">
                <i class="fas fa-exchange-alt nav-icon"></i>
                Transactions
            </a>

        </li>
        <li class="nav-group {{ request()->routeIs('admin.settings.*') ? 'show' : '' }}">

            <a class="nav-link" href="{{ route('admin.settings.index') }}">
                <i class="fas fa-cogs nav-icon"></i>
                Settings
            </a>
        </li>
      </ul>
      <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
      </div>
    </div>
