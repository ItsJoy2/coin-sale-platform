
@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')

<div class="container-fluid py-4 settings-page">

    {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h4 class="fw-bold mb-1">
                <i class="fas fa-cog text-primary me-2"></i>
                Settings
            </h4>

            <p class="text-muted mb-0">
                Manage your website, MIND sale, referral and payment settings.
            </p>
        </div>

    </div>

    @include('admin.components.alerts')


    {{-- =========================================================
        SETTINGS FORM
    ========================================================== --}}
    <form method="POST"
          action="{{ route('admin.settings.update') }}"
          id="settingsForm"
          enctype="multipart/form-data">

        @csrf
        @method('PUT')


        <div class="settings-layout">

            {{-- =================================================
                LEFT SIDEBAR
            ================================================== --}}
            <div class="settings-sidebar">

                <div class="settings-nav-card">

                    <div class="settings-nav-header">

                        <div class="nav-header-icon">
                            <i class="fas fa-sliders-h"></i>
                        </div>

                        <div>
                            <div class="fw-bold">
                                Settings
                            </div>

                            <small class="text-muted">
                                Configuration
                            </small>
                        </div>

                    </div>


                    <div class=" nav-item flex-column nav-pills settings-nav"
                         id="settingsTabs"
                         role="tablist">


                        @foreach($settings as $group => $groupSettings)

                            @php

                                $groupTitle = match($group) {

                                    'general' => 'General Settings',
                                    'mind' => 'MIND Settings',
                                    'purchase' => 'Purchase Settings',
                                    'referral' => 'Referral Settings',
                                    'system' => 'System Settings',
                                    'payment' => 'Payment Settings',

                                    default => ucwords(
                                        str_replace('_', ' ', $group)
                                    ),

                                };


                                $groupIcon = match($group) {

                                    'general' => 'fas fa-globe',
                                    'mind' => 'fas fa-coins',
                                    'purchase' => 'fas fa-shopping-cart',
                                    'referral' => 'fas fa-users',
                                    'system' => 'fas fa-sliders-h',
                                    'payment' => 'fas fa-credit-card',

                                    default => 'fas fa-cog',

                                };

                            @endphp


                            <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                    id="tab-{{ $group }}"
                                    data-bs-toggle="pill"
                                    data-bs-target="#content-{{ $group }}"
                                    type="button"
                                    role="tab">

                                <span class="settings-tab-icon">
                                    <i class="{{ $groupIcon }}"></i>
                                </span>

                                <span class="settings-tab-text">
                                    {{ $groupTitle }}
                                </span>

                                <i class="fas fa-chevron-right tab-arrow"></i>

                            </button>

                        @endforeach

                    </div>

                </div>

            </div>


            {{-- =================================================
                RIGHT CONTENT
            ================================================== --}}
            <div class="settings-content">

                <div class="tab-content"
                     id="settingsTabContent">


                    @foreach($settings as $group => $groupSettings)

                        @php

                            $groupTitle = match($group) {

                                'general' => 'General Settings',
                                'mind' => 'MIND Settings',
                                'purchase' => 'Purchase Settings',
                                'referral' => 'Referral Settings',
                                'system' => 'System Settings',
                                'payment' => 'Payment Settings',

                                default => ucwords(
                                    str_replace('_', ' ', $group)
                                ),

                            };


                            $groupIcon = match($group) {

                                'general' => 'fas fa-globe',
                                'mind' => 'fas fa-coins',
                                'purchase' => 'fas fa-shopping-cart',
                                'referral' => 'fas fa-users',
                                'system' => 'fas fa-sliders-h',
                                'payment' => 'fas fa-credit-card',

                                default => 'fas fa-cog',

                            };

                        @endphp


                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                             id="content-{{ $group }}"
                             role="tabpanel">


                            {{-- =============================================
                                GROUP HEADER
                            ============================================== --}}
                            <div class="content-header mb-4">

                                <div class="content-header-icon">
                                    <i class="{{ $groupIcon }}"></i>
                                </div>

                                <div>

                                    <h5 class="fw-bold mb-1">
                                        {{ $groupTitle }}
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Manage {{ strtolower($groupTitle) }}
                                    </p>

                                </div>

                            </div>


                            {{-- =============================================
                                SETTINGS CARD
                            ============================================== --}}
                            <div class="card settings-card border-0 shadow-sm">

                                <div class="card-body p-4">

                                    <div class="row g-4">


                                        @foreach($groupSettings as $setting)

                                            @php

                                                $fieldName =
                                                    'settings[' .
                                                    $setting->key .
                                                    ']';

                                                $fieldId =
                                                    'setting_' .
                                                    $setting->id;

                                                $label =
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $setting->key
                                                        )
                                                    );

                                            @endphp


                                            {{-- =================================================
                                                LOGO
                                            ================================================== --}}
                                            @if($setting->key === 'logo')


                                                <div class="col-12">

                                                    <div class="image-upload-card">

                                                        <div class="image-upload-preview">

                                                            @if($setting->value)

                                                                <img src="{{ asset('storage/' . $setting->value) }}"
                                                                     id="preview_logo"
                                                                     alt="Logo">

                                                            @else

                                                                <div class="empty-image"
                                                                     id="preview_logo_empty">

                                                                    <i class="fas fa-image"></i>

                                                                </div>

                                                            @endif

                                                        </div>


                                                        <div class="image-upload-content">

                                                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

                                                                <div>

                                                                    <h6 class="fw-bold mb-1">
                                                                        Website Logo
                                                                    </h6>

                                                                    <p class="text-muted small mb-3">
                                                                        Upload your website logo.
                                                                        Recommended 360×120 or 180×60 PNG.
                                                                    </p>

                                                                </div>

                                                            </div>


                                                            <label class="btn btn-primary rounded-3 px-3">

                                                                <i class="fas fa-upload me-1"></i>
                                                                Choose Logo

                                                                <input type="file"
                                                                       name="{{ $fieldName }}"
                                                                       id="logoInput"
                                                                       class="d-none"
                                                                       accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp">

                                                            </label>


                                                            <div class="selected-file-name mt-2"
                                                                 id="logoFileName">
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>


                                            {{-- =================================================
                                                FAVICON
                                            ================================================== --}}
                                            @elseif($setting->key === 'favicon')


                                                <div class="col-12">

                                                    <div class="image-upload-card">

                                                        <div class="image-upload-preview favicon-preview">

                                                            @if($setting->value)

                                                                <img src="{{ asset('storage/' . $setting->value) }}"
                                                                     id="preview_favicon"
                                                                     alt="Favicon">

                                                            @else

                                                                <div class="empty-image"
                                                                     id="preview_favicon_empty">

                                                                    <i class="fas fa-image"></i>

                                                                </div>

                                                            @endif

                                                        </div>


                                                        <div class="image-upload-content">

                                                            <h6 class="fw-bold mb-1">
                                                                Website Favicon
                                                            </h6>

                                                            <p class="text-muted small mb-3">
                                                                Upload the browser favicon.
                                                                Recommended 32×32 or 64×64 PNG/ICO.
                                                            </p>


                                                            <label class="btn btn-primary rounded-3 px-3">

                                                                <i class="fas fa-upload me-1"></i>
                                                                Choose Favicon

                                                                <input type="file"
                                                                       name="{{ $fieldName }}"
                                                                       id="faviconInput"
                                                                       class="d-none"
                                                                       accept="image/png,image/jpeg,image/jpg,image/x-icon,image/webp">

                                                            </label>


                                                            <div class="selected-file-name mt-2"
                                                                 id="faviconFileName">
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>


                                            {{-- =================================================
                                                PURCHASE SLOTS
                                            ================================================== --}}
                                            @elseif(
                                                $setting->type === 'json' &&
                                                $setting->key === 'purchase_slots'
                                            )


                                                @php

                                                    $slots = json_decode(
                                                        $setting->value,
                                                        true
                                                    ) ?? [];

                                                @endphp


                                                <div class="col-12">

                                                    <div class="purchase-slots-header">

                                                        <div>

                                                            <h6 class="fw-bold mb-1">

                                                                <i class="fas fa-layer-group text-primary me-2"></i>

                                                                Purchase Slots

                                                            </h6>

                                                            <small class="text-muted">

                                                                Configure purchase amount ranges
                                                                and MIND bonus percentages.

                                                            </small>

                                                        </div>


                                                        <button type="button"
                                                                class="btn btn-primary btn-sm rounded-3 px-3"
                                                                onclick="addPurchaseSlot()">

                                                            <i class="fas fa-plus me-1"></i>
                                                            Add Slot

                                                        </button>

                                                    </div>


                                                    <div id="purchaseSlotsContainer">

                                                        @foreach($slots as $index => $slot)

                                                            <div class="purchase-slot-card mb-3"
                                                                 data-slot-index="{{ $index }}">


                                                                <div class="slot-card-header">

                                                                    <div class="d-flex align-items-center">

                                                                        <div class="slot-number me-3">

                                                                            {{ $slot['slot'] ?? ($index + 1) }}

                                                                        </div>


                                                                        <div>

                                                                            <div class="fw-semibold">
                                                                                Purchase Slot
                                                                            </div>

                                                                            <small class="text-muted slot-title">
                                                                                Slot {{ $slot['slot'] ?? ($index + 1) }}
                                                                            </small>

                                                                        </div>

                                                                    </div>


                                                                    <button type="button"
                                                                            class="btn btn-outline-danger btn-sm rounded-3 remove-slot-btn"
                                                                            onclick="removePurchaseSlot(this)"
                                                                            {{ count($slots) <= 1 ? 'disabled' : '' }}>

                                                                        <i class="fas fa-trash-alt"></i>

                                                                    </button>

                                                                </div>


                                                                <div class="row g-3">


                                                                    {{-- SLOT --}}
                                                                    <div class="col-xl-2 col-md-6">

                                                                        <label class="form-label fw-semibold small">
                                                                            Slot Number
                                                                        </label>

                                                                        <input type="number"
                                                                               class="form-control slot-input"
                                                                               value="{{ $slot['slot'] ?? ($index + 1) }}"
                                                                               min="1"
                                                                               step="1" readonly disabled>

                                                                    </div>


                                                                    {{-- MIN --}}
                                                                    <div class="col-xl-3 col-md-6">

                                                                        <label class="form-label fw-semibold small">
                                                                            Minimum Amount
                                                                        </label>

                                                                        <div class="input-group">

                                                                            {{-- <span class="input-group-text">
                                                                                $
                                                                            </span> --}}

                                                                            <input type="number"
                                                                                   class="form-control min-usd-input"
                                                                                   value="{{ $slot['min_usd'] ?? 0 }}"
                                                                                   min="0"
                                                                                   step="0.01">

                                                                            <span class="input-group-text">
                                                                                $
                                                                            </span>

                                                                        </div>

                                                                    </div>


                                                                    {{-- MAX --}}
                                                                    <div class="col-xl-4 col-md-6">

                                                                        <label class="form-label fw-semibold small">
                                                                            Maximum Amount
                                                                        </label>

                                                                        <div class="input-group">

                                                                            {{-- <span class="input-group-text">
                                                                                $
                                                                            </span> --}}

                                                                            <input type="number"
                                                                                   class="form-control max-usd-input"
                                                                                   value="{{ $slot['max_usd'] ?? 0 }}"
                                                                                   min="0"
                                                                                   step="0.01">

                                                                            <span class="input-group-text">
                                                                                $
                                                                            </span>

                                                                        </div>

                                                                    </div>


                                                                    {{-- BONUS --}}
                                                                    <div class="col-xl-3 col-md-6">

                                                                        <label class="form-label fw-semibold small">
                                                                            Bonus Percentage
                                                                        </label>

                                                                        <div class="input-group">

                                                                            <input type="number"
                                                                                   class="form-control bonus-input"
                                                                                   value="{{ $slot['bonus_percentage'] ?? 0 }}"
                                                                                   min="0"
                                                                                   step="0.01">

                                                                            <span class="input-group-text">
                                                                                %
                                                                            </span>

                                                                        </div>

                                                                    </div>

                                                                </div>


                                                                <div class="slot-summary mt-3">

                                                                    <i class="fas fa-info-circle me-1"></i>

                                                                    <span class="slot-summary-text">

                                                                        ${{ number_format((float) ($slot['min_usd'] ?? 0), 2) }}

                                                                        -

                                                                        ${{ number_format((float) ($slot['max_usd'] ?? 0), 2) }}

                                                                        <span class="mx-2">•</span>

                                                                        {{ number_format((float) ($slot['bonus_percentage'] ?? 0), 2) }}%

                                                                        bonus

                                                                    </span>

                                                                </div>

                                                            </div>

                                                        @endforeach

                                                    </div>


                                                    <input type="hidden"
                                                           name="{{ $fieldName }}"
                                                           id="purchaseSlotsJson"
                                                           value="{{ $setting->value }}">

                                                </div>


                                            {{-- =================================================
                                                BOOLEAN
                                            ================================================== --}}
                                            @elseif($setting->type === 'boolean')


                                                <div class="col-12">

                                                    <div class="setting-toggle-card">

                                                        <div class="d-flex align-items-center">

                                                            <div class="setting-small-icon text-primary me-3">

                                                                <i class="fas fa-toggle-on"></i>

                                                            </div>


                                                            <div>

                                                                <div class="fw-semibold">
                                                                    {{ $label }}
                                                                </div>


                                                                @if($setting->description)

                                                                    <small class="text-muted">
                                                                        {{ $setting->description }}
                                                                    </small>

                                                                @endif

                                                            </div>

                                                        </div>


                                                        <div class="form-check form-switch mb-0">

                                                            <input type="hidden"
                                                                   name="{{ $fieldName }}"
                                                                   value="0">


                                                            <input type="checkbox"
                                                                   class="form-check-input setting-switch"
                                                                   id="{{ $fieldId }}"
                                                                   name="{{ $fieldName }}"
                                                                   value="1"
                                                                   {{ old(
                                                                        'settings.' . $setting->key,
                                                                        $setting->value
                                                                   ) ? 'checked' : '' }}>

                                                        </div>

                                                    </div>

                                                </div>


                                            {{-- =================================================
                                                STRING
                                            ================================================== --}}
                                            @elseif($setting->type === 'string')


                                                <div class="col-xl-6 col-md-6">

                                                    <div class="setting-field">

                                                        <label for="{{ $fieldId }}"
                                                               class="form-label fw-semibold">

                                                            {{ $label }}

                                                        </label>


                                                        <input type="text"
                                                               id="{{ $fieldId }}"
                                                               name="{{ $fieldName }}"
                                                               class="form-control @error('settings.' . $setting->key) is-invalid @enderror"
                                                               value="{{ old(
                                                                    'settings.' . $setting->key,
                                                                    $setting->value
                                                               ) }}">


                                                        @if($setting->description)

                                                            <div class="form-text">
                                                                {{ $setting->description }}
                                                            </div>

                                                        @endif


                                                        @error('settings.' . $setting->key)

                                                            <div class="invalid-feedback">
                                                                {{ $message }}
                                                            </div>

                                                        @enderror

                                                    </div>

                                                </div>


                                            {{-- =================================================
                                                DECIMAL
                                            ================================================== --}}
                                            @elseif($setting->type === 'decimal')

                                            <div class="col-xl-6 col-md-6">

                                                <div class="setting-field">

                                                    <label for="{{ $fieldId }}"
                                                        class="form-label fw-semibold">

                                                        {{ $label }}

                                                    </label>

                                                    <div class="input-group">

                                                        @if($setting->key === 'mind_price')

                                                            <span class="input-group-text">
                                                                $
                                                            </span>

                                                        @endif

                                                        <input type="number"
                                                            id="{{ $fieldId }}"
                                                            name="{{ $fieldName }}"
                                                            class="form-control @error('settings.' . $setting->key) is-invalid @enderror"
                                                            value="{{ number_format((float) old(
                                                                'settings.' . $setting->key,
                                                                $setting->value
                                                            ), 2, '.', '') }}"
                                                            step="0.01">

                                                        @if(str_contains($setting->key, 'percentage'))

                                                            <span class="input-group-text">
                                                                %
                                                            </span>

                                                        @endif

                                                    </div>

                                                    @if($setting->description)

                                                        <div class="form-text">
                                                            {{ $setting->description }}
                                                        </div>

                                                    @endif

                                                    @error('settings.' . $setting->key)

                                                        <div class="invalid-feedback d-block">
                                                            {{ $message }}
                                                        </div>

                                                    @enderror

                                                </div>

                                            </div>

                                            {{-- =================================================
                                                INTEGER
                                            ================================================== --}}
                                            @elseif($setting->type === 'integer')


                                                <div class="col-xl-6 col-md-6">

                                                    <div class="setting-field">

                                                        <label for="{{ $fieldId }}"
                                                               class="form-label fw-semibold">

                                                            {{ $label }}

                                                        </label>


                                                        <input type="number"
                                                               id="{{ $fieldId }}"
                                                               name="{{ $fieldName }}"
                                                               class="form-control @error('settings.' . $setting->key) is-invalid @enderror"
                                                               value="{{ old(
                                                                    'settings.' . $setting->key,
                                                                    $setting->value
                                                               ) }}"
                                                               step="1">


                                                        @if($setting->description)

                                                            <div class="form-text">
                                                                {{ $setting->description }}
                                                            </div>

                                                        @endif

                                                    </div>

                                                </div>


                                            {{-- =================================================
                                                OTHER JSON
                                            ================================================== --}}
                                            @elseif($setting->type === 'json')


                                                <div class="col-12">

                                                    <div class="setting-field">

                                                        <label for="{{ $fieldId }}"
                                                               class="form-label fw-semibold">

                                                            {{ $label }}

                                                        </label>


                                                        <textarea
                                                            id="{{ $fieldId }}"
                                                            name="{{ $fieldName }}"
                                                            class="form-control font-monospace @error('settings.' . $setting->key) is-invalid @enderror"
                                                            rows="6"
                                                        >{{ old(
                                                            'settings.' . $setting->key,
                                                            $setting->value
                                                        ) }}</textarea>


                                                        @if($setting->description)

                                                            <div class="form-text">
                                                                {{ $setting->description }}
                                                            </div>

                                                        @endif


                                                        @error('settings.' . $setting->key)

                                                            <div class="invalid-feedback d-block">
                                                                {{ $message }}
                                                            </div>

                                                        @enderror

                                                    </div>

                                                </div>

                                            @endif


                                        @endforeach

                                    </div>

                                </div>

                            </div>


                        </div>

                    @endforeach

                </div>

            </div>

        </div>


        {{-- =========================================================
            SAVE BAR
        ========================================================== --}}
        <div class="save-settings-bar">

            <div class="d-flex align-items-center">

                <div class="save-icon me-3">
                    <i class="fas fa-save"></i>
                </div>

                <div>

                    <div class="fw-bold">
                        Save Changes
                    </div>

                    <small class="text-muted">
                        Review your settings before saving.
                    </small>

                </div>

            </div>


            <button type="submit"
                    class="btn btn-primary px-4 rounded-3"
                    id="saveSettingsBtn">

                <i class="fas fa-save me-1"></i>
                Save Settings

            </button>

        </div>


    </form>

</div>


{{-- ============================================================
    CSS
============================================================= --}}
<style>

.settings-page {
    max-width: 1500px;
}


/* =============================================================
   MAIN LAYOUT
============================================================= */

.settings-layout {
    display: grid;
    grid-template-columns: 270px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}


/* =============================================================
   SIDEBAR
============================================================= */

.settings-sidebar {
    position: sticky;
    top: 20px;
}


/* .settings-nav-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 16px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, .05);
    overflow: hidden;
} */


.settings-nav-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    border-bottom: 1px solid #eef0f3;
}


.nav-header-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
}


.settings-nav {
    padding: 10px;
}


.settings-nav .nav-link {
    width: 100%;
    display: flex;
    align-items: center;
    text-align: left;
    gap: 11px;
    padding: 12px 13px;
    margin-bottom: 4px;
    /* color: #495057; */
    background: transparent;
    border-radius: 11px;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s ease;
}

.settings-tab-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    /* background: #f5f6f8; */
    font-size: 14px;
}


.settings-nav .nav-link.active .settings-tab-icon {
    /* background: #0d6efd; */
    /* color: #fff; */
}


.settings-tab-text {
    flex: 1;
}


.tab-arrow {
    font-size: 10px;
    opacity: .35;
}


.settings-nav .nav-link.active .tab-arrow {
    opacity: 1;
}


/* =============================================================
   CONTENT
============================================================= */

.settings-content {
    min-width: 0;
}


.content-header {
    display: flex;
    align-items: center;
    gap: 14px;
}


.content-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 13px;
    /* background: #eef4ff; */
    /* color: #0d6efd; */
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}


.settings-card {
    border-radius: 16px !important;
    overflow: hidden;
}


.settings-card .card-body {
    min-height: 200px;
}


/* =============================================================
   NORMAL SETTINGS
============================================================= */

.setting-field .form-label {
    font-size: 13px;
    margin-bottom: 7px;
}


.setting-field .form-control,
.setting-field .input-group-text {
    min-height: 44px;
}


.setting-field .form-control {
    border-radius: 9px;
}


.setting-field .input-group .form-control {
    border-radius: 0;
}


.setting-field .input-group .input-group-text:first-child {
    border-radius: 9px 0 0 9px;
}


.setting-field .input-group .input-group-text:last-child {
    border-radius: 0 9px 9px 0;
}


.form-text {
    font-size: 12px;
    margin-top: 6px;
}


/* =============================================================
   BOOLEAN
============================================================= */

.setting-toggle-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 18px;
    border: 1px solid #e9ecef;
    border-radius: 13px;
    /* background: #fafbfc; */
    transition: all .2s ease;
}





.setting-small-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 10px;
    /* background: #eef4ff; */
    display: flex;
    align-items: center;
    justify-content: center;
}


.setting-toggle-card .form-check-input {
    width: 2.8em;
    height: 1.45em;
    cursor: pointer;
}


/* =============================================================
   IMAGE UPLOAD
============================================================= */

.image-upload-card {
    display: flex;
    align-items: center;
    gap: 22px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    /* background: #fafbfc; */
}


.image-upload-preview {
    width: 150px;
    height: 90px;
    min-width: 150px;
    border-radius: 12px;
    border: 1px solid #e1e5ea;
    /* background: #fff; */
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}


.image-upload-preview img {
    max-width: 90%;
    max-height: 80%;
    object-fit: contain;
}


.favicon-preview {
    width: 90px;
    min-width: 90px;
    height: 90px;
}


.favicon-preview img {
    max-width: 60px;
    max-height: 60px;
}


.empty-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    /* color: #adb5bd; */
    font-size: 25px;
}


.image-upload-content {
    flex: 1;
}


.selected-file-name {
    font-size: 12px;
    /* color: #6c757d; */
}


/* =============================================================
   PURCHASE SLOTS
============================================================= */

.purchase-slots-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
}


.purchase-slot-card {
    padding: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    /* background: #fff; */
    transition: all .2s ease;
}


.purchase-slot-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 5px 20px rgba(0, 0, 0, .04);
}


.slot-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eef0f3;
}


.slot-number {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    /* background: #e9f0ff; */
    /* color: #0d6efd; */
    font-weight: 700;
}


.purchase-slot-card .form-control,
.purchase-slot-card .input-group-text {
    min-height: 43px;
}


.purchase-slot-card .form-control {
    border-radius: 8px;
}


.purchase-slot-card .input-group .form-control {
    border-radius: 0;
}


.purchase-slot-card .input-group .input-group-text:first-child {
    border-radius: 8px 0 0 8px;
}


.purchase-slot-card .input-group .input-group-text:last-child {
    border-radius: 0 8px 8px 0;
}


.slot-summary {
    padding: 10px 14px;
    /* background: #f8f9fa; */
    border-radius: 8px;
    /* color: #6c757d; */
    font-size: 12px;
}


.slot-summary i {
    /* color: #0d6efd; */
}


.remove-slot-btn {
    width: 36px;
    height: 36px;
}


/* =============================================================
   SAVE BAR
============================================================= */

.save-settings-bar {
    position: sticky;
    bottom: 15px;
    z-index: 100;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 15px 20px;
    margin-top: 24px;
    margin-bottom: 20px;
    /* background: rgba(255, 255, 255, .96); */
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, .08);
    backdrop-filter: blur(10px);
}


.save-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    /* background: #eef4ff;
    color: #0d6efd; */
    display: flex;
    align-items: center;
    justify-content: center;
}


.save-settings-bar .btn {
    min-height: 42px;
}


/* =============================================================
   MOBILE
============================================================= */

@media (max-width: 991px) {

    .settings-layout {
        grid-template-columns: 1fr;
    }


    .settings-sidebar {
        position: static;
    }


    .settings-nav-card {
        overflow-x: auto;
    }


    .settings-nav {
        display: flex;
        flex-direction: row !important;
        min-width: max-content;
        gap: 5px;
    }


    .settings-nav .nav-link {
        width: auto;
        min-width: 170px;
        margin-bottom: 0;
    }


    .tab-arrow {
        display: none;
    }

}


@media (max-width: 767px) {

    .settings-page {
        padding-left: 12px;
        padding-right: 12px;
    }


    .settings-card .card-body {
        padding: 18px !important;
    }


    .image-upload-card {
        flex-direction: column;
        align-items: flex-start;
    }


    .image-upload-preview {
        width: 100%;
        height: 110px;
    }


    .favicon-preview {
        width: 100%;
        height: 110px;
    }


    .purchase-slot-card {
        padding: 15px;
    }


    .purchase-slots-header {
        align-items: flex-start;
        flex-direction: column;
    }


    .purchase-slots-header .btn {
        width: 100%;
    }


    .save-settings-bar {
        flex-direction: column;
        align-items: stretch;
    }


    .save-settings-bar .btn {
        width: 100%;
    }

}

</style>


{{-- ============================================================
    JAVASCRIPT
============================================================= --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    updateRemoveButtons();

    updatePurchaseSlotsJson();

    setupImagePreview(
        'logoInput',
        'preview_logo',
        'preview_logo_empty',
        'logoFileName'
    );

    setupImagePreview(
        'faviconInput',
        'preview_favicon',
        'preview_favicon_empty',
        'faviconFileName'
    );

});


/*
|--------------------------------------------------------------------------
| IMAGE PREVIEW
|--------------------------------------------------------------------------
*/

function setupImagePreview(
    inputId,
    previewId,
    emptyId,
    fileNameId
) {

    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }


    input.addEventListener('change', function (event) {

        const file = event.target.files[0];

        if (!file) {
            return;
        }


        const fileNameElement =
            document.getElementById(fileNameId);


        if (fileNameElement) {

            fileNameElement.textContent =
                file.name;

        }


        const reader =
            new FileReader();


        reader.onload = function (e) {

            let image =
                document.getElementById(previewId);


            if (!image) {

                image =
                    document.createElement('img');

                image.id =
                    previewId;

                image.alt =
                    'Preview';


                const empty =
                    document.getElementById(emptyId);


                if (empty) {
                    empty.replaceWith(image);
                }

            }


            image.src =
                e.target.result;

        };


        reader.readAsDataURL(file);

    });

}


/*
|--------------------------------------------------------------------------
| ADD PURCHASE SLOT
|--------------------------------------------------------------------------
*/

function addPurchaseSlot()
{

    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    if (!container) {
        return;
    }


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    const nextSlot =
        cards.length + 1;


    const card =
        document.createElement('div');


    card.className =
        'purchase-slot-card mb-3';


    card.innerHTML = `

        <div class="slot-card-header">

            <div class="d-flex align-items-center">

                <div class="slot-number me-3">
                    ${nextSlot}
                </div>

                <div>

                    <div class="fw-semibold">
                        Purchase Slot
                    </div>

                    <small class="text-muted slot-title">
                        Slot ${nextSlot}
                    </small>

                </div>

            </div>


            <button type="button"
                    class="btn btn-outline-danger btn-sm rounded-3 remove-slot-btn"
                    onclick="removePurchaseSlot(this)">

                <i class="fas fa-trash-alt"></i>

            </button>

        </div>


        <div class="row g-3">


            <div class="col-xl-2 col-md-6">

                <label class="form-label fw-semibold small">
                    Slot Number
                </label>

                <input type="number"
                    class="form-control slot-input"
                    value="${nextSlot}"
                    min="1"
                    step="1"
                    readonly disabled>

            </div>


            <div class="col-xl-3 col-md-6">

                <label class="form-label fw-semibold small">
                    Minimum Amount
                </label>

                <div class="input-group">

                    // <span class="input-group-text">
                    //     $
                    // </span>

                    <input type="number"
                           class="form-control min-usd-input"
                           value="0"
                           min="0"
                           step="0.01">

                    <span class="input-group-text">
                        $
                    </span>

                </div>

            </div>


            <div class="col-xl-4 col-md-6">

                <label class="form-label fw-semibold small">
                    Maximum Amount
                </label>

                <div class="input-group">

                    // <span class="input-group-text">
                    //     $
                    // </span>

                    <input type="number"
                           class="form-control max-usd-input"
                           value="0"
                           min="0"
                           step="0.01">

                    <span class="input-group-text">
                        $
                    </span>

                </div>

            </div>


            <div class="col-xl-3 col-md-6">

                <label class="form-label fw-semibold small">
                    Bonus Percentage
                </label>

                <div class="input-group">

                    <input type="number"
                           class="form-control bonus-input"
                           value="0"
                           min="0"
                           step="0.01">

                    <span class="input-group-text">
                        %
                    </span>

                </div>

            </div>


        </div>


        <div class="slot-summary mt-3">

            <i class="fas fa-info-circle me-1"></i>

            <span class="slot-summary-text">

                $0.00 - $0.00

                <span class="mx-2">•</span>

                0.00% bonus

            </span>

        </div>

    `;


    container.appendChild(card);


    updateRemoveButtons();

    updatePurchaseSlotsJson();

}


/*
|--------------------------------------------------------------------------
| REMOVE PURCHASE SLOT
|--------------------------------------------------------------------------
*/

function removePurchaseSlot(button)
{

    const card =
        button.closest(
            '.purchase-slot-card'
        );


    if (!card) {
        return;
    }


    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    if (cards.length <= 1) {
        return;
    }


    card.remove();


    renumberPurchaseSlots();

    updateRemoveButtons();

    updatePurchaseSlotsJson();

}


/*
|--------------------------------------------------------------------------
| RENUMBER PURCHASE SLOTS
|--------------------------------------------------------------------------
*/

function renumberPurchaseSlots()
{

    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    if (!container) {
        return;
    }


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    cards.forEach(function (card, index) {

        const number =
            index + 1;


        const numberElement =
            card.querySelector(
                '.slot-number'
            );


        const titleElement =
            card.querySelector(
                '.slot-title'
            );


        const slotInput =
            card.querySelector(
                '.slot-input'
            );


        if (numberElement) {

            numberElement.textContent =
                number;

        }


        if (titleElement) {

            titleElement.textContent =
                'Slot ' + number;

        }


        if (slotInput) {

            slotInput.value =
                number;

        }

    });

}


/*
|--------------------------------------------------------------------------
| UPDATE REMOVE BUTTONS
|--------------------------------------------------------------------------
*/

function updateRemoveButtons()
{

    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    if (!container) {
        return;
    }


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    cards.forEach(function (card) {

        const button =
            card.querySelector(
                '.remove-slot-btn'
            );


        if (button) {

            button.disabled =
                cards.length <= 1;

        }

    });

}


/*
|--------------------------------------------------------------------------
| UPDATE SLOT SUMMARIES
|--------------------------------------------------------------------------
*/

function updateSlotSummaries()
{

    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    if (!container) {
        return;
    }


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    cards.forEach(function (card) {

        const minInput =
            card.querySelector(
                '.min-usd-input'
            );


        const maxInput =
            card.querySelector(
                '.max-usd-input'
            );


        const bonusInput =
            card.querySelector(
                '.bonus-input'
            );


        const summary =
            card.querySelector(
                '.slot-summary-text'
            );


        if (!summary) {
            return;
        }


        const min =
            parseFloat(
                minInput?.value || 0
            );


        const max =
            parseFloat(
                maxInput?.value || 0
            );


        const bonus =
            parseFloat(
                bonusInput?.value || 0
            );


        summary.innerHTML =

            '$' +
            min.toFixed(2)

            +

            ' - $' +
            max.toFixed(2)

            +

            '<span class="mx-2">•</span>'

            +

            bonus.toFixed(2) +
            '% bonus';

    });

}


/*
|--------------------------------------------------------------------------
| UPDATE PURCHASE SLOT JSON
|--------------------------------------------------------------------------
*/

function updatePurchaseSlotsJson()
{

    const container =
        document.getElementById(
            'purchaseSlotsContainer'
        );


    const jsonInput =
        document.getElementById(
            'purchaseSlotsJson'
        );


    if (!container || !jsonInput) {
        return;
    }


    const cards =
        container.querySelectorAll(
            '.purchase-slot-card'
        );


    const slots = [];


    cards.forEach(function (card, index) {

        const slot =
            parseInt(
                card.querySelector(
                    '.slot-input'
                )?.value
                || (index + 1)
            );


        const minUsd =
            parseFloat(
                card.querySelector(
                    '.min-usd-input'
                )?.value
                || 0
            );


        const maxUsd =
            parseFloat(
                card.querySelector(
                    '.max-usd-input'
                )?.value
                || 0
            );


        const bonus =
            parseFloat(
                card.querySelector(
                    '.bonus-input'
                )?.value
                || 0
            );


        slots.push({

            slot: slot,

            min_usd: minUsd,

            max_usd: maxUsd,

            bonus_percentage: bonus

        });

    });


    jsonInput.value =
        JSON.stringify(slots);


    updateSlotSummaries();

}


/*
|--------------------------------------------------------------------------
| INPUT EVENT
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'input',
    function (event) {

        if (

            event.target.classList.contains(
                'slot-input'
            )

            ||

            event.target.classList.contains(
                'min-usd-input'
            )

            ||

            event.target.classList.contains(
                'max-usd-input'
            )

            ||

            event.target.classList.contains(
                'bonus-input'
            )

        ) {

            updatePurchaseSlotsJson();

        }

    }
);


/*
|--------------------------------------------------------------------------
| FORM SUBMIT
|--------------------------------------------------------------------------
*/

const settingsForm =
    document.getElementById(
        'settingsForm'
    );


if (settingsForm) {

    settingsForm.addEventListener(
        'submit',
        function () {

            updatePurchaseSlotsJson();


            const button =
                document.getElementById(
                    'saveSettingsBtn'
                );


            if (button) {

                button.disabled =
                    true;


                button.innerHTML =

                    '<i class="fas fa-spinner fa-spin me-1"></i>' +

                    ' Saving...';

            }

        }
    );

}

</script>

@endsection

