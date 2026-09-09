/**
 * A single confirmation dialog, shared by every page.
 *
 * Programmatic:
 *     confirmDialog({ title, message, confirmLabel, cancelLabel, tone })
 *         .then(function (ok) { if (ok) ... });
 *
 * Declarative - works on any form or link, no JavaScript needed at the call site:
 *     <form data-confirm="Delete this school?" data-confirm-title="Delete school">
 *     <a href="..." data-confirm="Leave without saving?" data-confirm-tone="primary">
 *
 * Tones: 'danger' (default) and 'primary'.
 */
(function () {
    'use strict';

    var TONES = {
        danger: { icon: 'fa-triangle-exclamation', label: 'Delete' },
        primary: { icon: 'fa-circle-question', label: 'Confirm' }
    };

    var el = {};
    var settle = null;
    var lastFocused = null;

    function cache() {
        if (el.root) return el.root;

        el.root = document.getElementById('confirmDialog');
        if (!el.root) return null;

        el.card = el.root.querySelector('.confirm-card');
        el.icon = el.root.querySelector('.confirm-icon i');
        el.title = el.root.querySelector('.confirm-title');
        el.message = el.root.querySelector('.confirm-message');
        el.accept = el.root.querySelector('.confirm-accept');
        el.cancel = el.root.querySelector('.confirm-cancel');

        el.accept.addEventListener('click', function () { close(true); });
        el.cancel.addEventListener('click', function () { close(false); });

        // Clicking the backdrop, but not the card, cancels.
        el.root.addEventListener('mousedown', function (event) {
            if (event.target === el.root) close(false);
        });

        document.addEventListener('keydown', function (event) {
            if (!el.root.classList.contains('is-open')) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                close(false);
            } else if (event.key === 'Tab') {
                // Two buttons, so the trap is just a swap between them.
                event.preventDefault();
                (document.activeElement === el.accept ? el.cancel : el.accept).focus();
            }
        });

        return el.root;
    }

    function open(options) {
        var opts = typeof options === 'string' ? { message: options } : (options || {});

        if (!cache()) {
            // No dialog on the page (a guest layout, say) - fall back to the browser's.
            return Promise.resolve(window.confirm(opts.message || 'Are you sure?'));
        }

        var tone = TONES[opts.tone] ? opts.tone : 'danger';

        el.root.dataset.tone = tone;
        el.icon.className = 'fas ' + (opts.icon || TONES[tone].icon);
        el.title.textContent = opts.title || 'Are you sure?';
        el.message.textContent = opts.message || '';
        el.message.hidden = !opts.message;
        el.accept.textContent = opts.confirmLabel || TONES[tone].label;
        el.cancel.textContent = opts.cancelLabel || 'Cancel';

        lastFocused = document.activeElement;
        el.root.classList.add('is-open');
        el.root.setAttribute('aria-hidden', 'false');

        // Reading a layout property flushes the style change, so the buttons are
        // no longer visibility:hidden and can actually take focus. Cancel goes
        // first, so a stray Enter never deletes anything.
        void el.root.offsetWidth;
        el.cancel.focus();

        return new Promise(function (resolve) {
            settle = resolve;
        });
    }

    function close(result) {
        if (!el.root) return;

        el.root.classList.remove('is-open');
        el.root.setAttribute('aria-hidden', 'true');

        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
        lastFocused = null;

        var resolve = settle;
        settle = null;
        if (resolve) resolve(Boolean(result));
    }

    function optionsFrom(node) {
        return {
            message: node.dataset.confirm,
            title: node.dataset.confirmTitle,
            confirmLabel: node.dataset.confirmLabel,
            cancelLabel: node.dataset.confirmCancel,
            tone: node.dataset.confirmTone
        };
    }

    // Any form with data-confirm asks first, then submits for real. Calling
    // form.submit() does not fire 'submit' again, so this cannot loop.
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-confirm]');
        if (!form || form.dataset.confirmed === 'yes') return;

        event.preventDefault();
        open(optionsFrom(form)).then(function (ok) {
            if (!ok) return;
            form.dataset.confirmed = 'yes';
            form.submit();
        });
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-confirm]');
        if (!link) return;

        event.preventDefault();
        open(optionsFrom(link)).then(function (ok) {
            if (ok) window.location.href = link.href;
        });
    });

    window.confirmDialog = open;
})();
