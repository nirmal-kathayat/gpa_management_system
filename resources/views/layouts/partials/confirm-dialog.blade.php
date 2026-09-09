{{--
    One dialog for the whole app, driven by public/assets/js/confirm-dialog.js.
    Replaces window.confirm(), which cannot be styled and looks like the browser
    rather than the product.
--}}
<div class="confirm-backdrop" id="confirmDialog" aria-hidden="true">
    <div class="confirm-card" role="alertdialog" aria-modal="true"
         aria-labelledby="confirmDialogTitle" aria-describedby="confirmDialogMessage">
        <span class="confirm-icon"><i class="fas fa-triangle-exclamation"></i></span>
        <h2 class="confirm-title" id="confirmDialogTitle">Are you sure?</h2>
        <p class="confirm-message" id="confirmDialogMessage"></p>
        <div class="confirm-actions">
            <button type="button" class="confirm-cancel">Cancel</button>
            <button type="button" class="confirm-accept">Delete</button>
        </div>
    </div>
</div>
