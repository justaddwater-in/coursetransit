document.addEventListener('DOMContentLoaded', function () {

    let lastFocused = null;

    const subject = document.getElementById('coursetransit-subject');
    const textarea = document.getElementById('body');
    const button = document.getElementById('coursetransit-load-default');

    // Track focus
    if (subject) subject.addEventListener('focus', () => lastFocused = 'subject');
    if (textarea) textarea.addEventListener('focus', () => lastFocused = 'body');

    // Handle tag click
    document.querySelectorAll('.coursetransit-tag').forEach(tag => {
        tag.style.cursor = 'pointer';

        tag.addEventListener('click', function () {
            const value = this.dataset.tag;

            // 1. Insert into subject if last focused
            if (lastFocused === 'subject' && subject) {
                insertAtCursor(subject, value);
                subject.focus();
                return;
            }

            // 2. Try TinyMCE
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get('body');
                if (editor && !editor.isHidden()) {
                    editor.execCommand('mceInsertContent', false, value);
                    editor.focus();
                    return;
                }
            }

            // 3. Fallback to textarea
            if (textarea) {
                insertAtCursor(textarea, value);
                textarea.focus();
            }
        });
    });

    // Default template content
    const defaultTemplates = {
        enrollment: `
    <p>Hi {first_name},</p>

    <p>
    We’re excited to let you know that you’ve been successfully enrolled in:
    </p>

    <p style="font-size:16px;">
    <strong>{course_name}</strong>
    </p>

    <p>
    You can start learning immediately by logging into your dashboard here:
    </p>

    <p>
    <a href="{login_url}" target="_blank" rel="noopener">
        Access your course
    </a>
    </p>

    <hr>

    <p><strong>Your account details:</strong></p>

    <ul>
    <li>Email: {email}</li>
    <li>Password: {password}</li>
    </ul>

    <p>
    If you have any questions, just reply to this email — we’re happy to help.
    </p>

    <p>
    Happy learning,<br>
    <strong>The {site_name} Team</strong>
    </p>

    <p style="font-size:12px;color:#6b7280;">
    If the button above doesn’t work, copy and paste this link into your browser:<br>
    {login_url}
    </p>
    `,

        enrollment_existing: `
    <p>Hi {first_name},</p>

    <p>
    You have been successfully added to the course:
    </p>

    <p style="font-size:16px;">
    <strong>{course_name}</strong>
    </p>

    <p>
    You can continue learning by logging into your dashboard:
    </p>

    <p>
    <a href="{login_url}" target="_blank" rel="noopener">
        Go to your dashboard
    </a>
    </p>

    <p>
    Happy learning,<br>
    <strong>The {site_name} Team</strong>
    </p>
    `
    };


    // Handle default template button
    if (button) {
        button.addEventListener('click', function () {

            Swal.fire({
                title: 'Use default template?',
                text: 'This will replace the current email content.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, use default',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2271b1',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'button button-primary px-4',
                    cancelButton: 'button button-secondary mx-2',
                },
                buttonsStyling: false
            }).then((result) => {

                if (!result.isConfirmed) return;

                // Try TinyMCE first
                if (typeof tinymce !== 'undefined') {
                    const editor = tinymce.get('body');
                    if (editor && !editor.isHidden()) {
                        const selected = document.getElementById('coursetransit-template-select').value;
                        editor.setContent(defaultTemplates[selected] || '');
                        editor.focus();
                        return;
                    }
                }

                // Fallback to textarea
                if (textarea) {
                    const selected = document.getElementById('coursetransit-template-select').value;
                    textarea.value = defaultTemplates[selected] || '';
                    textarea.focus();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Template Loaded',
                    text: 'Default email template applied.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        });
    }

    function insertAtCursor(field, value) {
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;

        field.value =
            field.value.substring(0, start) +
            value +
            field.value.substring(end);

        field.selectionStart = field.selectionEnd = start + value.length;
    }

});


document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('coursetransit-email-form');
    if (!form) return;

    let notice = document.getElementById('coursetransit-save-notice');

    if (!notice) {
        notice = document.createElement('div');
        notice.id = 'coursetransit-save-notice';
        form.prepend(notice);
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const subject = document.getElementById('coursetransit-subject').value;

        let body = '';
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('body');
            if (editor && !editor.isHidden()) {
                body = editor.getContent();
            }
        }

        if (!body) {
            body = document.getElementById('body').value;
        }

        const data = new FormData();
        const template = document.getElementById('coursetransit-template-select').value;

        data.append('action', 'coursetransit_save_email_template');
        data.append('_ajax_nonce', CourseTransitAjax.nonce);
        data.append('template', template);
        data.append('subject', subject);
        data.append('body', body);

        fetch(CourseTransitAjax.ajax_url, {
            method: 'POST',
            body: data
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Template Saved',
                        text: res.data?.message || 'Email template saved successfully.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: res.data?.message || 'Error saving template.'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Save Failed',
                    text: 'Unable to save template. Please try again.'
                });
            });
    });

});

document.addEventListener('DOMContentLoaded', function () {

    const testBtn = document.getElementById('coursetransit-send-test');
    if (!testBtn) return;

    testBtn.addEventListener('click', function () {

        Swal.fire({
            title: 'Send Test Email',
            input: 'email',
            inputLabel: 'Recipient email address',
            inputPlaceholder: 'you@example.com',
            showCancelButton: true,
            confirmButtonText: 'Send Test',
            confirmButtonColor: '#2271b1',
            reverseButtons: true,
            customClass: {
                confirmButton: 'button button-primary px-4',
                cancelButton: 'button button-secondary mx-2'
            },
            buttonsStyling: false,
            inputValidator: value => {
                if (!value) return 'Please enter an email address';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Enter a valid email';
            }
        }).then(result => {

            if (!result.isConfirmed) return;

            // Collect subject + body
            const subject = document.getElementById('coursetransit-subject').value;

            let body = '';
            if (typeof tinymce !== 'undefined') {
                const editor = tinymce.get('body');
                if (editor && !editor.isHidden()) {
                    body = editor.getContent();
                }
            }
            if (!body) body = document.getElementById('body').value;

            const data = new FormData();
            const template = document.getElementById('coursetransit-template-select').value;

            data.append('action', 'coursetransit_send_test_email');
            data.append('_ajax_nonce', CourseTransitAjax.nonce);
            data.append('template', template);
            data.append('email', result.value);
            data.append('subject', subject);
            data.append('body', body);

            Swal.fire({
                title: 'Sending…',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(CourseTransitAjax.ajax_url, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Sent',
                            text: 'Test email delivered successfully.'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Send Failed',
                            text: res.data?.error || 'Unable to send email.'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Send Failed',
                        text: 'Server error occurred.'
                    });
                });
        });
    });

});

// Load template on selection change
document.addEventListener('DOMContentLoaded', function () {

    const select = document.getElementById('coursetransit-template-select');
    if (!select) return;

    select.addEventListener('change', function () {

        const data = new FormData();
        data.append('action', 'coursetransit_get_email_template');
        data.append('_ajax_nonce', CourseTransitAjax.nonce);
        data.append('template', this.value);

        fetch(CourseTransitAjax.ajax_url, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => {
                if (!res.success) return;

                document.getElementById('coursetransit-subject').value = res.data.subject || '';

                const applyBody = () => {
                    const editor = tinymce.get('body');
                    if (editor && !editor.isHidden()) {
                        editor.setContent(res.data.body || '');
                    } else {
                        document.getElementById('body').value = res.data.body || '';
                    }
                };

                if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                    applyBody();
                } else {
                    setTimeout(applyBody, 100);
                }
            });
    });

});
