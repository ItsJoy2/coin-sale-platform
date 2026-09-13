@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="cil-check-circle me-2"></i>
        {{ session('success') }}

        <button
            type="button"
            class="btn-close"
            data-coreui-dismiss="alert"
            aria-label="Close">
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="cil-x-circle me-2"></i>
        {{ session('error') }}

        <button
            type="button"
            class="btn-close"
            data-coreui-dismiss="alert"
            aria-label="Close">
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="cil-warning me-2"></i>
        {{ session('warning') }}

        <button
            type="button"
            class="btn-close"
            data-coreui-dismiss="alert"
            aria-label="Close">
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">

        <strong>Please fix the following errors:</strong>

        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>

        <button
            type="button"
            class="btn-close"
            data-coreui-dismiss="alert"
            aria-label="Close">
        </button>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {

    setTimeout(function () {

        document.querySelectorAll('.alert').forEach(function(alert) {

            const instance = coreui.Alert.getOrCreateInstance(alert);
            instance.close();

        });

    }, 5000);

});
</script>
