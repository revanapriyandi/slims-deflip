(function () {
    'use strict';

    var guestForm = document.getElementById('deflip-guest-form');
    if (guestForm) {
        guestForm.addEventListener('submit', function () {
            if (!guestForm.checkValidity()) return;
            var button = guestForm.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = button.getAttribute('data-loading-label');
        });
    }

    var settingsForm = document.getElementById('deflipSettings');
    if (!settingsForm || settingsForm.getAttribute('data-bound') === 'true') return;
    settingsForm.setAttribute('data-bound', 'true');
    if (window.deflipSettingsFeedbackHandler) {
        document.removeEventListener('deflip:settings-saved', window.deflipSettingsFeedbackHandler);
    }
    window.deflipSettingsFeedbackHandler = function (event) {
        var response = event.detail;
        var feedback = document.getElementById('deflip-settings-feedback');
        if (!feedback) return;
        feedback.textContent = response.message;
        feedback.className = response.success ? 'alert alert-success' : 'alert alert-danger';
        feedback.hidden = false;
        var token = settingsForm.querySelector('input[name="csrf_token"]');
        if (token && response.token) token.value = response.token;
        var button = settingsForm.querySelector('input[name="saveData"]');
        if (button) button.removeAttribute('aria-busy');
    };
    document.addEventListener('deflip:settings-saved', window.deflipSettingsFeedbackHandler);
    var field = settingsForm.querySelector('textarea[name="tos"]');
    var editorElement = document.getElementById('deflip-tos-editor');
    var toolbar = document.getElementById('deflip-tos-toolbar');
    var richEditor = document.getElementById('deflip-tos-rich');

    if (typeof DecoupledEditor !== 'undefined' && editorElement) {
        DecoupledEditor.create(editorElement, {
            toolbar: ['bold', 'italic', 'link', 'numberedList', 'bulletedList', 'undo', 'redo']
        }).then(function (editor) {
            toolbar.appendChild(editor.ui.view.toolbar.element);
            richEditor.hidden = false;
            field.hidden = true;
            editor.enableReadOnlyMode && settingsForm.getAttribute('data-readonly') === 'true' && editor.enableReadOnlyMode('deflip');
            settingsForm.addEventListener('submit', function () { field.value = editor.getData(); });
        }).catch(function () {
            richEditor.hidden = true;
            field.hidden = false;
        });
    }

    settingsForm.addEventListener('submit', function () {
        var button = settingsForm.querySelector('input[name="saveData"]');
        if (button) button.setAttribute('aria-busy', 'true');
    });
}());
