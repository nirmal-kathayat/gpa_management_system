/**
 * Toasts, stacked under the topbar on the right.
 *
 *     toast.success('School updated successfully!');
 *     toast.error('Something went wrong', { duration: 8000 });
 *     toast('Plain message', 'info');
 *
 * Flash messages from the server are rendered into the stack by
 * resources/views/components/toast.blade.php; this file only gives those the
 * same dismiss button and timer as the ones raised from JavaScript.
 */
(function () {
    'use strict';

    var DEFAULT_DURATION = 5000;

    var ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    function stack() {
        var node = document.getElementById('toastStack');

        if (!node) {
            node = document.createElement('div');
            node.id = 'toastStack';
            node.className = 'toast-stack no-print';
            node.setAttribute('aria-live', 'polite');
            document.body.appendChild(node);
        }

        return node;
    }

    function dismiss(item) {
        if (item.dataset.leaving === 'yes') return;

        item.dataset.leaving = 'yes';
        item.classList.add('is-leaving');
        window.clearTimeout(Number(item.dataset.timer));

        // Falls back to a plain removal if the browser skips the transition.
        var done = function () { if (item.parentNode) item.parentNode.removeChild(item); };
        item.addEventListener('transitionend', done, { once: true });
        window.setTimeout(done, 400);
    }

    function arm(item, duration) {
        if (duration <= 0) return;

        var start = function () {
            item.dataset.timer = window.setTimeout(function () { dismiss(item); }, duration);
        };

        // Reading a toast should not race the timer.
        item.addEventListener('mouseenter', function () {
            window.clearTimeout(Number(item.dataset.timer));
        });
        item.addEventListener('mouseleave', start);

        start();
    }

    function wire(item, duration) {
        var close = item.querySelector('.toast-close');
        if (close) close.addEventListener('click', function () { dismiss(item); });

        arm(item, typeof duration === 'number' ? duration : DEFAULT_DURATION);
    }

    function show(message, type, options) {
        var opts = options || {};
        var kind = ICONS[type] ? type : 'info';

        var item = document.createElement('div');
        item.className = 'toast-item toast-' + kind;
        item.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        item.innerHTML =
            '<i class="fas ' + ICONS[kind] + ' toast-icon" aria-hidden="true"></i>' +
            '<span class="toast-text"></span>' +
            '<button type="button" class="toast-close" aria-label="Dismiss">' +
            '<i class="fas fa-xmark" aria-hidden="true"></i></button>';
        item.querySelector('.toast-text').textContent = message;

        stack().appendChild(item);
        wire(item, opts.duration);

        return item;
    }

    var toast = function (message, type, options) { return show(message, type, options); };

    Object.keys(ICONS).forEach(function (kind) {
        toast[kind] = function (message, options) { return show(message, kind, options); };
    });

    toast.dismissAll = function () {
        Array.prototype.forEach.call(document.querySelectorAll('.toast-item'), dismiss);
    };

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(document.querySelectorAll('.toast-item'), function (item) {
            wire(item, Number(item.dataset.duration) || DEFAULT_DURATION);
        });
    });

    window.toast = toast;
})();
