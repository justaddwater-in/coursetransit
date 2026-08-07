jQuery(function ($) {

    /**
     * GENERAL SETTINGS
     */
    const form = document.getElementById('coursetransit-settings-form');

    if (form) {

        const btn = form.querySelector('button[type="submit"]');

        if (!btn) {
            console.error('Save button not found');
            return;
        }

        const originalText = btn.innerText;

        const testBtn = document.getElementById(
            'coursetransit-test-connection'
        );

        let mode = 'save';

        // TEST BUTTON
        if (testBtn) {

            testBtn.addEventListener('click', function (e) {

                e.preventDefault();

                mode = 'test';

                form.dispatchEvent(
                    new Event('submit', { cancelable: true })
                );

            });

        }

        // SAVE BUTTON
        btn.addEventListener('click', function () {
            mode = 'save';
        });

        // FORM SUBMIT
        form.addEventListener('submit', function (e) {

            e.preventDefault();

            const isTest = mode === 'test';

            Swal.fire({
                title: isTest ? 'Test Connection?' : 'Save Settings?',
                text: isTest
                    ? 'We will verify the Moodle connection.'
                    : 'We will verify the connection and save your settings.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: isTest
                    ? 'Test Connection'
                    : 'Save & Test',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#2271b1',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'button button-primary px-4',
                    cancelButton: 'button button-secondary mx-2',
                },
                buttonsStyling: false

            }).then(result => {

                if (!result.isConfirmed) {
                    return;
                }

                btn.disabled = true;

                btn.innerText = isTest
                    ? 'Testing connection...'
                    : 'Saving settings...';

                Swal.fire({
                    title: isTest
                        ? 'Testing connection...'
                        : 'Saving settings...',
                    text: isTest
                        ? 'Please wait while Moodle is being verified.'
                        : 'Please wait while your settings are being saved.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                const formData = new FormData(form);

                formData.append(
                    'action',
                    'coursetransit_settings_test'
                );

                formData.append(
                    '_ajax_nonce',
                    CourseTransitAjax.nonce
                );

                fetch(CourseTransitAjax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.text())
                    .then(raw => {

                        let json;

                        try {

                            json = JSON.parse(raw);

                        } catch {

                            throw new Error('Invalid JSON response');

                        }

                        btn.disabled = false;
                        btn.innerText = originalText;

                        if (!json.success) {

                            Swal.fire({
                                icon: 'error',
                                title: 'Connection Failed',
                                html: `
                                    <pre style="
                                        text-align:left;
                                        max-height:300px;
                                        overflow:auto;
                                    ">
${JSON.stringify(json.data || json, null, 2)}
                                    </pre>
                                `
                            });

                            return;

                        }

                        const data =
                            json?.data?.connection || {};

                        Swal.fire({
                            icon: 'success',
                            title: isTest
                                ? 'Connection Successful'
                                : 'Settings Saved',

                            html: `
                                <div style="
                                    text-align:left;
                                    background:#f9fafb;
                                    border:1px solid #e5e7eb;
                                    border-radius:10px;
                                    padding:14px;
                                ">

                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        gap:12px;
                                        margin-bottom:10px;
                                    ">

                                        ${data.avatar ? `
                                            <img
                                                src="${data.avatar}"
                                                style="
                                                    width:48px;
                                                    height:48px;
                                                    border-radius:50%;
                                                    object-fit:cover;
                                                ">
                                        ` : ''}

                                        <div>

                                            <div style="
                                                font-weight:600;
                                                font-size:15px;
                                            ">
                                                ${data.user || 'N/A'}
                                            </div>

                                            <div style="
                                                font-size:12px;
                                                color:#6b7280;
                                            ">
                                                ${data.username
                                    ? `(${data.username})`
                                    : ''}
                                            </div>

                                        </div>

                                    </div>

                                    <div style="
                                        font-size:13px;
                                        margin-bottom:8px;
                                    ">

                                        <strong>
                                            ${data.site_name || 'N/A'}
                                        </strong>

                                        <span style="
                                            color:#6b7280;
                                            margin-left:6px;
                                        ">
                                            ${data.version || ''}
                                        </span>

                                    </div>

                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        gap:6px;
                                        font-size:13px;
                                        color:#16a34a;
                                        font-weight:500;
                                    ">
                                        <span style="font-size:16px;">
                                            ●
                                        </span>

                                        Connected
                                    </div>

                                </div>
                            `,

                            confirmButtonText: 'OK',

                            customClass: {
                                confirmButton:
                                    'button button-primary px-4'
                            },

                            buttonsStyling: false
                        });

                    })
                    .catch(error => {

                        console.error(error);

                        btn.disabled = false;

                        btn.innerText = originalText;

                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Unexpected server error occurred'
                        });

                    });

            });

        });

    }

    /**
     * LOAD SETTINGS
     */
    $(document).on('shown.bs.tab', '#sync-tab', function () {
        loadCourseTransitSettings();
    });

    /**
     * SAVE SYNC SETTINGS
     */
    $(document).on('click', '#cts-settings-save', function () {

        const $btn = $(this);

        if ($btn.prop('disabled')) {
            return;
        }

        $btn
            .prop('disabled', true)
            .text('Saving...');

        Swal.fire({
            title: 'Saving...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.post(CourseTransitAjax.ajax_url, {

            action: 'coursetransit_save_course_settings',

            default_enrollment_period:
                $('#cts-default-enrollment-period').val(),

            product_status:
                $('#cts-product-status').val(),

            product_content_sync:
                $('#cts-product-content-sync').val(),

            sync_curriculum:
                $('#cts-sync-curriculum').val(),

            category_sync:
                $('#cts-category-sync').val(),

            image_sync:
                $('#cts-image-sync').val(),

            missing_course_action:
                $('#cts-missing-course-action').val(),

            _ajax_nonce:
                CourseTransitAjax.nonce

        })
            .done(res => {

                $btn
                    .prop('disabled', false)
                    .text('Save Settings');

                if (!res.success) {

                    Swal.fire(
                        'Error',
                        res?.data?.message ||
                        'Failed to save settings',
                        'error'
                    );

                    return;

                }

                Swal.fire({
                    icon: 'success',
                    title: 'Settings Saved',
                    timer: 1500,
                    showConfirmButton: false
                });

            })
            .fail(() => {

                $btn
                    .prop('disabled', false)
                    .text('Save Settings');

                Swal.fire(
                    'Error',
                    'Unexpected server error occurred',
                    'error'
                );

            });

    });

    /**
     * LOAD SYNC SETTINGS
     */
    function loadCourseTransitSettings() {

        if (
            !document.getElementById(
                'cts-default-enrollment-period'
            )
        ) {
            return;
        }

        $.get(CourseTransitAjax.ajax_url, {

            action:
                'coursetransit_get_course_settings',

            _ajax_nonce:
                CourseTransitAjax.nonce

        }).done(res => {

            if (!res.success) {
                return;
            }

            const s = res.data || {};

            $('#cts-default-enrollment-period')
                .val(
                    String(
                        s.default_enrollment_period || 0
                    )
                );

            $('#cts-product-status')
                .val(
                    String(
                        s.product_status || 'publish'
                    )
                );

            $('#cts-product-content-sync')
                .val(
                    String(
                        s.product_content_sync || 'all'
                    )
                );

            $('#cts-sync-curriculum')
                .val(
                    String(
                        s.sync_curriculum || 'all'
                    )
                );

            $('#cts-category-sync')
                .val(
                    String(
                        s.category_sync || 'all'
                    )
                );

            $('#cts-image-sync')
                .val(
                    String(
                        s.image_sync || 'all'
                    )
                );

            $('#cts-missing-course-action')
                .val(
                    String(
                        s.missing_course_action || 'draft'
                    )
                );

        });

    }

});
