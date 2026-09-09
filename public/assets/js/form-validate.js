/**
 * Client-side validation that matches the server's.
 *
 * The browser's own bubble ("Please fill out this field.") is turned off and the
 * message is written under the field instead, in the same place and style Laravel
 * uses when it sends the form back.
 *
 * Applies to every .form-card form, and to any form marked data-validate.
 */
(function () {
    'use strict';

    var SELECTOR = 'form.form-card, form[data-validate]';

    function fieldWrap(field) {
        return field.closest('.form-field') || field.parentElement;
    }

    function labelText(field) {
        var label = field.id && field.form
            ? field.form.querySelector('label[for="' + CSS.escape(field.id) + '"]')
            : null;

        if (!label) return 'This field';

        // Drop the required marker and any trailing punctuation.
        return label.textContent.replace('*', '').trim().replace(/[:：]$/, '');
    }

    function message(field) {
        var v = field.validity;
        var name = labelText(field);

        if (v.valueMissing) {
            return field.tagName === 'SELECT'
                ? 'Choose a ' + name.toLowerCase() + '.'
                : name + ' is required.';
        }
        if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address.';
        if (v.typeMismatch) return 'Enter a valid ' + name.toLowerCase() + '.';
        if (v.rangeUnderflow) return name + ' cannot be less than ' + field.min + '.';
        if (v.rangeOverflow) return name + ' cannot be more than ' + field.max + '.';
        if (v.stepMismatch) return name + ' must be a whole number.';
        if (v.tooShort) return name + ' must be at least ' + field.minLength + ' characters.';
        if (v.tooLong) return name + ' must be at most ' + field.maxLength + ' characters.';
        if (v.patternMismatch) return name + ' is not in the expected format.';

        return field.validationMessage;
    }

    function clear(field) {
        field.classList.remove('is-invalid');

        var existing = fieldWrap(field).querySelector('.form-error.is-client');
        if (existing) existing.remove();
    }

    function mark(field) {
        clear(field);
        field.classList.add('is-invalid');

        var error = document.createElement('p');
        error.className = 'form-error is-client';
        error.textContent = message(field);
        fieldWrap(field).appendChild(error);
    }

    function check(field) {
        if (field.willValidate === false) return true;

        if (field.checkValidity()) {
            clear(field);
            return true;
        }

        mark(field);
        return false;
    }

    function wire(form) {
        if (form.dataset.validateWired === 'yes') return;
        form.dataset.validateWired = 'yes';

        // Our messages replace the browser's, so its own bubbles must not fire.
        form.setAttribute('novalidate', 'novalidate');

        form.addEventListener('submit', function (event) {
            var invalid = Array.prototype.filter.call(form.elements, function (field) {
                return !check(field);
            });

            if (!invalid.length) return;

            event.preventDefault();
            event.stopPropagation();

            // A field can be hidden behind a widget (Select2 replaces the
            // <select>); focus what the person can actually see.
            var target = invalid[0].offsetParent === null
                ? (fieldWrap(invalid[0]).querySelector('.select2-selection') || invalid[0])
                : invalid[0];

            target.focus();
            target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        });

        // Once a field is fixed the message goes, rather than waiting for a resubmit.
        form.addEventListener('input', function (event) {
            if (event.target.classList.contains('is-invalid')) check(event.target);
        });

        form.addEventListener('change', function (event) {
            if (event.target.classList.contains('is-invalid')) check(event.target);
        });
    }

    function wireAll() {
        Array.prototype.forEach.call(document.querySelectorAll(SELECTOR), wire);
    }

    document.addEventListener('DOMContentLoaded', wireAll);
    window.wireFormValidation = wireAll;
})();
