{{--
    Fixed bottom bar. Like the topbar it stays put while the page body scrolls,
    which is why .page-body reserves --footer-h of bottom padding for it.
--}}
<footer class="app-footer no-print">
    <span class="footer-copy">
        &copy; {{ now()->year }} GPA Management System. All rights reserved.
    </span>
    <span class="footer-note">Version 1.0</span>
</footer>
