@extends('admin.layouts.auth')

@section('auth-content')
    <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
      <div class="container" style="max-width: 32rem">
        <div class="d-flex flex-column gap-4">
            @php
                $siteLogo = \App\Models\Setting::where('key', 'logo')->value('value');
            @endphp
            <img src=" {{ $siteLogo ? asset('storage/' . $siteLogo) : asset('assets/mindchsinwallet.png') }}" alt="Mindchain Logo" class="sidebar-brand-full" height="60" style="margin: 0 auto; display: block;">

          <div class="card p-4">
            <div class="card-body d-flex flex-column gap-4">
              <h2 class="h5 text-center">Login to your account</h2>
                <form class="row gap-3" action="{{ route('admin.login.submit') }}" method="POST" autocomplete="off" novalidate>

                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger mb-0">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div>
                        <label class="form-label" for="login">
                            Email address or Username
                        </label>

                        <input
                            class="form-control @error('login') is-invalid @enderror"
                            id="login"
                            name="login"
                            type="text"
                            value="{{ old('login') }}"
                            placeholder="your@email.com"
                            autocomplete="off"
                            required>

                        @error('login')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <div class="input-group">
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Your password"
                                autocomplete="off"
                                required>

                            <span class="input-group-text">
                                <button
                                    class="bg-transparent border-0 p-0 link-secondary"
                                    type="button"
                                    onclick="togglePassword()">

                                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                        <path class="ci-primary" fill="var(--ci-primary-color, currentcolor)" d="M256 144.927a103.309 103.309 0 1 0 103.309 103.309A103.426 103.426 0 0 0 256 144.927m0 174.618a71.309 71.309 0 1 1 71.309-71.309A71.39 71.39 0 0 1 256 319.545"></path>
                                        <path class="ci-primary" fill="var(--ci-primary-color, currentcolor)" d="m397.222 131.1-.218-.223c-77.75-77.749-204.258-77.749-282.008 0L16 233.79v28.893l98.778 102.689.218.222a199.41 199.41 0 0 0 282.008 0l99-102.911V233.79ZM464 249.79l-89.732 93.285a167.41 167.41 0 0 1-236.536 0L48 249.79v-3.107l89.729-93.283c65.247-65.13 171.3-65.13 236.542 0L464 246.683Z"></path>
                                        <path class="ci-primary" fill="var(--ci-primary-color, currentcolor)" d="M240 232h32v32h-32z"></path>
                                    </svg>

                                </button>
                            </span>
                        </div>

                        @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div>
                        <label class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="remember"
                                value="1">

                            <span class="form-check-label">
                                Remember me on this device
                            </span>
                        </label>
                    </div>

                    <div>
                        <button class="btn btn-primary w-100" type="submit">
                            Sign in
                        </button>
                    </div>
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
@endsection


<script>
    function togglePassword() {
        const password = document.getElementById('password');

        password.type = password.type === 'password'
            ? 'text'
            : 'password';
    }
</script>
