const defaultTemplates = (typeof CourseTransitEmailConfig !== 'undefined' && CourseTransitEmailConfig.templates) ? CourseTransitEmailConfig.templates : {};
const templateOptions = (typeof CourseTransitEmailConfig !== 'undefined' && CourseTransitEmailConfig.options) ? CourseTransitEmailConfig.options : {};
const basePreview = (typeof CourseTransitEmailConfig !== 'undefined' && CourseTransitEmailConfig.preview) ? CourseTransitEmailConfig.preview : {};

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('coursetransit-email-form');
    const select = document.getElementById('coursetransit-template-select');
    const subject = document.getElementById('coursetransit-subject');
    const textarea = document.getElementById('body');
    const defaultButton = document.getElementById('coursetransit-load-default');
    const previewButton = document.getElementById('coursetransit-preview');
    const testButton = document.getElementById('coursetransit-send-test');
    const count = document.getElementById('ct-subject-count');
    const status = document.getElementById('coursetransit-template-status');
    if (!form || !select) return;

    let lastTarget = 'body';

    const getEditor = () => (typeof tinymce !== 'undefined' ? tinymce.get('body') : null);
    const getBody = () => {
        const editor = getEditor();
        if (editor && !editor.isHidden()) return editor.getContent();
        return textarea ? textarea.value : '';
    };
    const setBody = value => {
        const editor = getEditor();
        if (editor) editor.setContent(value || '');
        if (textarea) textarea.value = value || '';
    };
    const insertAtCursor = (field, value) => {
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;
        field.value = field.value.substring(0, start) + value + field.value.substring(end);
        field.selectionStart = field.selectionEnd = start + value.length;
    };
    const refreshCount = () => { if (count) count.textContent = `${(subject.value || '').length} / 180`; };

    subject.addEventListener('focus', () => { lastTarget = 'subject'; });
    if (textarea) textarea.addEventListener('focus', () => { lastTarget = 'body'; });
    document.addEventListener('focusin', e => { if (e.target.closest && e.target.closest('.mce-content-body')) lastTarget = 'body'; });
    subject.addEventListener('input', refreshCount);
    refreshCount();

    function renderTags(templateKey) {
        const groups = {
            'Recipient': ['{first_name}', '{last_name}', '{email}', '{username}', '{password}'],
            'Course': ['{course_name}', '{bundle_name}', '{course_count}', '{course_list}'],
            'Purchase & Quote': ['{total_seats}', '{order_number}', '{quote_number}'],
            'Account & Site': ['{site_name}', '{login_url}', '{set_password_url}', '{my_licenses_url}', '{my_quotes_url}']
        };
        const tags = (typeof CourseTransitEmailConfig !== 'undefined' && CourseTransitEmailConfig.tags && CourseTransitEmailConfig.tags[templateKey]) || {};
        const holder = document.getElementById('coursetransit-tag-groups');
        if (!holder) return;
        holder.innerHTML = '';
        Object.keys(groups).forEach(group => {
            const available = groups[group].filter(tag => Object.prototype.hasOwnProperty.call(tags, tag));
            if (!available.length) return;
            const section = document.createElement('section');
            section.className = 'ct-tag-group';
            section.innerHTML = `<h6>${group}</h6><div class="ct-tag-list">${available.map(tag => `<button type="button" class="ct-tag-chip coursetransit-tag" data-tag="${tag}"><span>${tags[tag]}</span><code>${tag}</code></button>`).join('')}</div>`;
            holder.appendChild(section);
        });
        holder.querySelectorAll('.coursetransit-tag').forEach(bindTag);
    }

    function bindTag(tag) {
        tag.addEventListener('click', function () {
            const value = this.dataset.tag;
            if (lastTarget === 'subject') {
                insertAtCursor(subject, value);
                subject.focus();
                refreshCount();
                return;
            }
            const editor = getEditor();
            if (editor) {
                editor.execCommand('mceInsertContent', false, value);
                editor.focus();
                return;
            }
            if (textarea) { insertAtCursor(textarea, value); textarea.focus(); }
        });
    }
    document.querySelectorAll('.coursetransit-tag').forEach(bindTag);

    function applyTemplate(data, key) {
        const savedSubject = (data && data.subject || '').trim();
        const savedBody = (data && data.body || '').trim();
        const defaults = defaultTemplates[key] || {};
        subject.value = savedSubject || defaults.subject || '';
        setBody(savedBody || defaults.body || '');
        status.textContent = savedSubject || savedBody ? 'Editing saved template' : 'Editing default template';
        refreshCount();
        renderTags(key);
    }

    function loadTemplate(key) {
        const data = new FormData();
        data.append('action', 'coursetransit_get_email_template');
        data.append('_ajax_nonce', CourseTransitAjax.nonce);
        data.append('template', key);
        fetch(CourseTransitAjax.ajax_url, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => { if (res.success) applyTemplate(res.data || {}, key); });
    }

    select.addEventListener('change', () => loadTemplate(select.value));

    defaultButton.addEventListener('click', function () {
        const key = select.value;
        Swal.fire({ title: 'Use default template?', text: 'Your current edits for this template will be replaced.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Use Default', cancelButtonText: 'Cancel', reverseButtons: true }).then(result => {
            if (!result.isConfirmed) return;
            applyTemplate({}, key);
            Swal.fire({ icon: 'success', title: 'Default loaded', text: 'The default template is ready to save.', timer: 1300, showConfirmButton: false });
        });
    });

    previewButton.addEventListener('click', function () {
        const key = select.value;
        const replacements = basePreview[key] || basePreview.enrollment || {};
        let html = getBody();
        Object.keys(replacements).forEach(tag => { html = html.split(tag).join(replacements[tag]); });
        Swal.fire({ title: 'Email Preview', html: `<div class="ct-preview-body">${html}</div>`, width: 820, showCloseButton: true, showConfirmButton: false });
    });

    testButton.addEventListener('click', function () {
        Swal.fire({ title: 'Send Test Email', input: 'email', inputLabel: 'Recipient email address', inputPlaceholder: 'you@example.com', showCancelButton: true, confirmButtonText: 'Send Test', reverseButtons: true, inputValidator: value => !value ? 'Please enter an email address' : (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? 'Enter a valid email' : undefined) }).then(result => {
            if (!result.isConfirmed) return;
            const data = new FormData();
            data.append('action', 'coursetransit_send_test_email');
            data.append('_ajax_nonce', CourseTransitAjax.nonce);
            data.append('template', select.value);
            data.append('email', result.value);
            data.append('subject', subject.value);
            data.append('body', getBody());
            Swal.fire({ title: 'Sending…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            fetch(CourseTransitAjax.ajax_url, { method: 'POST', body: data }).then(r => r.json()).then(res => {
                if (res.success) Swal.fire({ icon: 'success', title: 'Test email sent', text: res.data?.message || 'WordPress accepted the email.' });
                else Swal.fire({ icon: 'error', title: 'Send failed', text: res.data?.error || res.data?.message || 'Unable to send the test email.' });
            }).catch(() => Swal.fire({ icon: 'error', title: 'Send failed', text: 'The server returned an unexpected response.' }));
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const data = new FormData();
        data.append('action', 'coursetransit_save_email_template');
        data.append('_ajax_nonce', CourseTransitAjax.nonce);
        data.append('template', select.value);
        data.append('subject', subject.value);
        data.append('body', getBody());
        fetch(CourseTransitAjax.ajax_url, { method: 'POST', body: data }).then(r => r.json()).then(res => {
            if (res.success) { status.textContent = 'Saved just now'; Swal.fire({ icon: 'success', title: 'Template saved', timer: 1400, showConfirmButton: false }); }
            else Swal.fire({ icon: 'error', title: 'Save failed', text: res.data?.message || res.data || 'Unable to save template.' });
        }).catch(() => Swal.fire({ icon: 'error', title: 'Save failed', text: 'Unable to save the template. Please try again.' }));
    });

    if (typeof CourseTransitEmailConfig !== 'undefined' && CourseTransitEmailConfig.tags) renderTags(select.value);
});
