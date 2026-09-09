{{-- Shared behaviour for every signed-in page. --}}
<script>
    // Grid rows cannot hold a Blade <form>, so a delete action posts one after
    // the shared dialog says yes. The second argument is either a message or
    // the full set of confirmDialog options.
    window.tableDelete = function (url, options) {
        const opts = typeof options === 'string' ? { message: options } : (options || {});

        window.confirmDialog({
            title: opts.title || 'Delete this record?',
            message: opts.message,
            confirmLabel: opts.confirmLabel || 'Delete',
            tone: 'danger'
        }).then(function (confirmed) {
            if (!confirmed) return;

            const token = document.querySelector('meta[name="csrf-token"]').content;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML =
                '<input type="hidden" name="_token" value="' + token + '">' +
                '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        });
    };

    // After a failed submit, put the cursor where the problem is.
    document.addEventListener('DOMContentLoaded', function () {
        const firstInvalid = document.querySelector('.form-input.is-invalid');
        if (firstInvalid) firstInvalid.focus({ preventScroll: false });
    });

    // The sidebar slides in over the page below 992px.
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        if (!sidebar || !toggle) return;

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            sidebar.classList.toggle('show');
        });

        document.addEventListener('click', function (event) {
            if (window.innerWidth > 991) return;
            if (sidebar.contains(event.target)) return;
            sidebar.classList.remove('show');
        });
    });
</script>
