    <!-- necessary plugins-->
    <script src="{{ asset('assets/vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/simplebar/js/simplebar.min.js') }}"></script>
    <script>
      const header = document.querySelector("header.header");

      document.addEventListener("scroll", () => {
        if (header) {
          header.classList.toggle("shadow-sm", document.documentElement.scrollTop > 0);
        }
      });
    </script>
    <!-- Plugins and scripts required by this view-->
    <script src="{{ asset('assets/vendors/chart.js/js/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/vendors/@coreui/chartjs/js/coreui-chartjs.js') }}"></script>
    <script src="{{ asset('assets/vendors/@coreui/utils/js/index.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.local-datetime').forEach(function (element) {

                const dateString = element.dataset.datetime;

                if (!dateString) {
                    element.textContent = 'N/A';
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


        document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.copy-wallet').forEach(function (button) {

        button.addEventListener('click', function () {

            const wallet = this.dataset.wallet;
            const icon = this.querySelector('i');

            if (!wallet) {
                return;
            }

            navigator.clipboard.writeText(wallet)
                .then(() => {

                    icon.classList.remove('fa-copy');
                    icon.classList.add('fa-check');

                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-success');

                    this.setAttribute('title', 'Copied!');

                    setTimeout(() => {

                        icon.classList.remove('fa-check');
                        icon.classList.add('fa-copy');

                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-secondary');

                        this.setAttribute(
                            'title',
                            'Copy wallet address'
                        );

                    }, 1500);

                })
                .catch(() => {
                    alert('Unable to copy wallet address.');
                });

        });

    });

});
    </script>

    @stack('auth_scripts')
