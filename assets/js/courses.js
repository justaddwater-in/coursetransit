jQuery(function ($) {

    $('#coursetransit-sync-courses').on('click', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const originalText = $btn.text();

        Swal.fire({
            title: 'Sync Courses?',
            text: 'This will fetch and update courses from Moodle.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, sync now',
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

            $btn.prop('disabled', true).text('Syncing…');

            let offset = 0;
            const limit = 5;

            function syncBatch() {

                $.post(CourseTransitAjax.ajax_url, {
                    action: 'coursetransit_sync_courses',
                    offset: offset,
                    limit: limit,
                    processed_once: window._sync_initialized ? 1 : 0,
                    _ajax_nonce: CourseTransitAjax.nonce
                })

                    .done(function (res) {

                        if (!res || res.success !== true) {

                            let errorMsg = res?.data?.message || 'Course sync failed.';

                            Swal.fire({
                                icon: 'error',
                                title: 'Sync Failed',
                                text: errorMsg
                            });

                            $btn.prop('disabled', false).text(originalText);
                            return;
                        }

                        const data = res.data;

                        if (data.init) {
                            Swal.update({
                                title: `Syncing 0/${data.total}`
                            });

                            offset = data.next_offset;

                            window._sync_initialized = true;

                            syncBatch();
                            return;
                        }

                        if (typeof data.total === 'undefined' || typeof data.processed === 'undefined') {
                            Swal.fire('Error', 'Invalid server response', 'error');
                            $btn.prop('disabled', false).text(originalText);
                            return;
                        }

                        const percent = Math.round((data.processed / data.total) * 100);

                        // Update UI
                        Swal.update({
                            title: `Syncing ${data.processed}/${data.total}`
                        });

                        $('#sync-progress-text').html(
                            `<b>${data.processed}</b> of <b>${data.total}</b> courses synced`
                        );

                        $('#sync-progress-bar').css('width', percent + '%');

                        if (!data.done && data.processed < data.total) {
                            offset = data.next_offset;
                            syncBatch();
                        } else {

                            Swal.fire({
                                icon: 'success',
                                title: 'Sync Complete',
                                text: 'Courses synced successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            $('#coursetransit-courses-table').DataTable().ajax.reload();

                            $btn.prop('disabled', false).text(originalText);
                        }
                    })

                    .fail(function (xhr) {

                        let errorMsg = 'Unable to reach server.';

                        if (xhr.responseJSON?.data?.message) {
                            errorMsg = xhr.responseJSON.data.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: errorMsg
                        });

                        $btn.prop('disabled', false).text(originalText);
                    });
            }

            // Start syncing (locked modal)
            Swal.fire({
                title: 'Syncing 0/0',
                html: `
                <div style="margin-top:10px;">
                    <div id="sync-progress-text">Starting...</div>

                    <div style="margin-top:15px;">
                        <div style="
                            height:6px;
                            background:#e5e7eb;
                            border-radius:4px;
                            overflow:hidden;
                        ">
                            <div id="sync-progress-bar" style="
                                width:0%;
                                height:100%;
                                background:#2271b1;
                                transition:width .3s ease;
                            "></div>
                        </div>
                    </div>
                </div>
            `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    syncBatch();
                }
            });
        });
    });

    $(document).on('click', '.sync-course', function (e) {
        e.preventDefault();

        const moodleId = $(this).data('moodle-id');
        const $btn = $(this);
        const originalText = $btn.text();

        Swal.fire({
            title: 'Sync this course?',
            text: 'This will update this course from Moodle.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, sync',
            cancelButtonText: 'Cancel',
        }).then((result) => {

            if (!result.isConfirmed) return;

            $btn.prop('disabled', true).text('Syncing…');

            $.post(CourseTransitAjax.ajax_url, {
                action: 'coursetransit_sync_single_course',
                moodle_id: moodleId,
                _ajax_nonce: CourseTransitAjax.nonce
            })
                .done(function (res) {

                    if (!res || res.success !== true) {
                        Swal.fire('Failed', res?.data?.message || 'Sync failed', 'error');
                        return;
                    }

                    Swal.fire('Success', 'Course synced successfully', 'success');

                    $('#coursetransit-courses-table').DataTable().ajax.reload(null, false);
                })
                .fail(function () {
                    Swal.fire('Error', 'Server error', 'error');
                })
                .always(function () {
                    $btn.prop('disabled', false).text(originalText);
                });
        });
    });

    const table = $('#coursetransit-courses-table').DataTable({
        processing: true,
        pageLength: 10,
        lengthChange: false,
        autoWidth: false,
        ordering: true,
        search: {
            smart: false
        },
        language: {
            search: ''
        },

        order: [], // fullname column

        ajax: {
            url: CourseTransitAjax.ajax_url,
            type: 'GET',
            dataSrc: 'data',
            data: {
                action: 'coursetransit_courses_table',
                _ajax_nonce: CourseTransitAjax.nonce
            }
        },

        columns: [
            { data: 'sr', orderable: false },     // disable for #
            { data: 'image', orderable: false },  // disable for Image
            { data: 'fullname' },
            // { data: 'shortname' },
            { data: 'enrolled_count', orderable: false },
            { data: 'visible' },
            // {
            //     data: 'last_synced_at',
            //     className: 'dt-left'
            // },
            { data: 'price', orderable: false },
            {
                data: 'actions',
                orderable: false,
                width: "260px",
                className: 'coursetransit-actions-col'
            }// disable for Actions
        ]
    });



    /**
     * IMPORTANT: Force width recalculation
     * Fixes squeezed table in Shards / Bootstrap layouts
     */
    table.on('draw', function () {
        table.columns.adjust();
    });

    /**
     * Customize search input
     */
    $('#coursetransit-courses-table').on('init.dt', function () {
        const $input = $('#coursetransit-courses-table_filter input');

        $input
            .addClass('form-control')
            .attr('placeholder', 'Search courses…');
    });

    // VIEW ACTIVITIES
    $(document).on('click', '.view-activities', function () {

        const courseId = $(this).data('id');

        $('#coursetransit-activities-loading').show();
        $('#coursetransit-activities-content').addClass('d-none');
        $('#coursetransit-activities-table').empty();
        $('#course-actions').empty();

        const modal = new bootstrap.Modal(document.getElementById('coursetransitActivitiesModal'));
        modal.show();

        $.get(CourseTransitAjax.ajax_url, {
            action: 'coursetransit_courses_activities',
            id: courseId,
            _ajax_nonce: CourseTransitAjax.nonce
        }).done(res => {

            $('#coursetransit-activities-loading').hide();

            if (!res.success) return;

            const c = res.data.course;

            $('#course-name').text(c.fullname);
            $('#course-shortname').text(c.shortname);
            $('#course-status').text(c.visible);
            $('#course-synced').text('Last synced: ' + c.last_synced);

            if (CourseTransitAjax.moodle_base_url && c.moodle_id) {
                const moodleUrl = CourseTransitAjax.moodle_base_url + '/course/view.php?id=' + c.moodle_id;

                $('#course-moodle-id').html(`
                    <a href="${moodleUrl}" 
                    target="_blank" 
                    class="coursetransit-link-btn">
                    Open in Moodle
                    </a>
                `);
            } else {
                $('#course-moodle-id').text('—');
            }

            $('#course-format').text(c.format || '—');
            const curriculum = Array.isArray(res.data.curriculum) ? res.data.curriculum : [];
            $('#course-sections').text(curriculum.length);
            $('#course-lang').text(c.lang || '—');
            $('#course-maxbytes').text(c.max_bytes);
            $('#course-completion').text(c.completion);
            $('#course-grades').text(c.show_grades);
            $('#course-product-status').text(c.product_status || '—');
            $('#course-enrolled').text(c.enrolled_count || 0);
            $('#course-activities-count').text(c.activity_count || 0);
            $('#course-price').html(c.price || '—');
            $('#course-enrollment-period').text(
                c.enrollment_period || '—'
            );
            $('#course-categories').text(
                Array.isArray(c.categories)
                    ? c.categories.join(', ')
                    : '—'
            );
            $('#course-start').text(c.start_date);
            $('#course-end').text(c.end_date);

            $('#course-image').attr(
                'src',
                c.image || CourseTransitAjax.placeholder_image
            );

            if (c.product.view) {
                $('#course-actions').append(`
                <a href="${c.product.view}" target="_blank" class="button button-small">
                    Public Page
                </a>
            `);
            }

            if (c.product.edit) {
                $('#course-actions').append(`
                <a href="${c.product.edit}" class="button button-small">
                    Edit
                </a>
            `);
            }

            if (!res.data.curriculum.length) {
                $('#coursetransit-no-activities').removeClass('d-none');
                $('#coursetransit-activities-content').removeClass('d-none');
                return;
            }

            // res.data.curriculum.forEach(section => {

            //     $('#coursetransit-activities-table').append(`
            //         <tr style="background:#f9fafb;font-weight:600;">
            //         <td colspan="2">${section.title}</td>
            //         </tr>
            //     `);

            //     section.items.forEach(item => {
            //         $('#coursetransit-activities-table').append(`
            //         <tr>
            //             <td style="padding-left:20px;">${item.title}</td>
            //             <td>${item.type}</td>
            //         </tr>
            //         `);
            //     });

            // });
            const $accordion = $('#coursetransit-curriculum-accordion');
            $accordion.empty();

            res.data.curriculum.forEach((section, index) => {

                const collapseId = `coursetransit-collapse-${index}`;
                const open = index === 0 ? 'show' : '';

                let itemsHtml = '';

                if (section.items && section.items.length) {
                    section.items.forEach(item => {

                        let typeKey = (item.type || '').toLowerCase();
                        let typeLabel = item.type;
                        let icon = 'description'; // default icon

                        // Mapping with Material Icons
                        if (typeKey === 'knowledgecheck') {
                            typeLabel = 'Video + Knowledge Check';
                            icon = 'smart_display'; // video + quiz feel
                        }
                        else if (typeKey === 'quiz') {
                            icon = 'quiz';
                        }
                        else if (typeKey === 'resource') {
                            icon = 'description';
                        }
                        else if (typeKey === 'video') {
                            icon = 'play_circle';
                        }
                        else if (typeKey === 'assignment') {
                            icon = 'assignment';
                        }
                        else if (typeKey === 'page') {
                            icon = 'article';
                        }
                        else if (typeKey === 'file') {
                            icon = 'insert_drive_file';
                        }
                        else if (typeKey === 'customcert') {
                            icon = 'workspace_premium'; // certificate icon
                        }


                        itemsHtml += `
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">

                                        <div class="d-flex align-items-center" style="gap:8px;">
                                            <span class="material-icons" style="font-size:18px;color:#6b7280;">
                                                ${icon}
                                            </span>
                                            <span class="ct-font" >${item.title}</span>
                                        </div>

                                        <span class="badge bg-secondary">${typeLabel}</span>

                                    </div>
                                `;
                    });


                } else {
                    itemsHtml = `
                        <div class="text-muted text-center py-3">
                            No activities
                        </div>
                    `;
                }

                $accordion.append(`
                    <div class="card mb-1">
                        <div class="card-header p-2">
                            <a class="d-flex justify-content-between align-items-center text-decoration-none"
                            data-bs-toggle="collapse"
                            href="#${collapseId}"
                            role="button"
                            aria-expanded="${index === 0}"
                            aria-controls="${collapseId}">
                            
                                <strong>${section.title}</strong>
                                <i class="material-icons">expand_more</i>
                            </a>
                        </div>

                        <div id="${collapseId}" class="collapse ${open}">
                            <div class="card-body p-0">
                                ${itemsHtml}
                            </div>
                        </div>
                    </div>
                `);
            });

            $('#coursetransit-activities-content').removeClass('d-none');
        });
    });


    // SLIDE IN
    $('#coursetransitActivitiesModal').on('shown.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(0)');
    });

    // SLIDE OUT
    $('#coursetransitActivitiesModal').on('hide.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(100%)');
    });

    // QUICK EDIT SLIDE IN
    $('#coursetransitQuickEditModal').on('shown.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(0)');
    });

    // QUICK EDIT SLIDE OUT
    $('#coursetransitQuickEditModal').on('hide.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(100%)');
    });

    // QUICK EDIT 
    $(document).on('click', '.quick-edit-course', function () {

        const productId = $(this).data('product-id');

        // show modal
        const modal = new bootstrap.Modal(document.getElementById('coursetransitQuickEditModal'));
        modal.show();

        // show loading
        $('#qe-loading').show();
        $('#qe-content').addClass('d-none');

        // reset fields
        $('#qe-price').val('');
        $('#qe-sale-price').val('');
        $('#qe-status').val('publish');

        // fetch product
        $.get(CourseTransitAjax.ajax_url, {
            action: 'coursetransit_get_product',
            product_id: productId,
            _ajax_nonce: CourseTransitAjax.nonce
        }).done(res => {

            $('#qe-loading').hide();

            if (!res.success) {
                Swal.fire('Error', 'Failed to load product', 'error');
                return;
            }

            const p = res.data;

            // basic
            $('#qe-name').val(p.name || '');
            $('#qe-slug').val(p.slug || '');

            // pricing
            $('#qe-price').val(p.price || '');
            $('#qe-sale-price').val(p.sale_price || '');

            // inventory
            $('#qe-sku').val(p.sku || '');

            // visibility
            $('#qe-visibility').val(p.visibility || 'visible');
            $('#qe-featured').prop('checked', p.featured === 'yes');

            // status
            $('#qe-status').val(p.status || 'publish');

            // enrollment period
            const enrollmentPeriod = p.enrollment_period;

            const presetValues = ['0', '30', '60', '90', '180', '365'];

            if (
                enrollmentPeriod === '' ||
                enrollmentPeriod === null ||
                typeof enrollmentPeriod === 'undefined'
            ) {

                $('#qe-enrollment-period').val('');

                $('#qe-custom-enrollment-wrap').hide();

                $('#qe-custom-enrollment').val('');

            } else if (presetValues.includes(String(enrollmentPeriod))) {

                $('#qe-enrollment-period')
                    .val(String(enrollmentPeriod));

                $('#qe-custom-enrollment-wrap').hide();

                $('#qe-custom-enrollment').val('');

            } else {

                $('#qe-enrollment-period').val('custom');

                $('#qe-custom-enrollment-wrap').show();

                $('#qe-custom-enrollment')
                    .val(enrollmentPeriod);
            }

            // header
            $('#qe_course-name').text(p.course_name || p.name || 'Course');
            $('#qe_course-shortname').text(p.course_shortname || '');

            $('#qe_course-status').text(p.course_status || '');
            $('#qe_course-synced').text(
                p.last_synced ? 'Last synced: ' + p.last_synced : ''
            );

            $('#qe_course-image').attr(
                'src',
                p.course_image || CourseTransitAjax.placeholder_image
            );

            // store id
            $('#coursetransitQuickEditModal').data('product-id', productId);

            // show content
            $('#qe-content').removeClass('d-none');
            if (p.edit_url) {
                $('#qe-full-edit').attr({
                    href: p.edit_url,
                    target: '_blank'
                });
            }
        });

    });

    // QUICK EDIT SAVE
    $(document).on('click', '#qe-save', function () {

        const productId = $('#coursetransitQuickEditModal').data('product-id');

        Swal.fire({
            title: 'Saving...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.post(CourseTransitAjax.ajax_url, {
            action: 'coursetransit_update_product',
            product_id: productId,

            name: $('#qe-name').val(),
            slug: $('#qe-slug').val(),

            price: $('#qe-price').val(),
            sale_price: $('#qe-sale-price').val(),

            sku: $('#qe-sku').val(),

            visibility: $('#qe-visibility').val(),
            featured: $('#qe-featured').is(':checked') ? 'yes' : 'no',

            status: $('#qe-status').val(),

            enrollment_period: $('#qe-enrollment-period').val(),
            custom_enrollment_period: $('#qe-custom-enrollment').val(),

            _ajax_nonce: CourseTransitAjax.nonce
        }).done(res => {

            if (!res.success) {
                Swal.fire('Error', res.data.message, 'error');
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Saved',
                timer: 1500,
                showConfirmButton: false
            });

            $('#coursetransitQuickEditModal').modal('hide');

            $('#coursetransit-courses-table').DataTable().ajax.reload(null, false);
        });

    });

    $(document).on('click', '#qe-cancel', function () {
        $('#coursetransitQuickEditModal').modal('hide');
    });

    $(document).on('change', '#qe-enrollment-period', function () {

        const value = $(this).val();

        if (value === 'custom') {
            $('#qe-custom-enrollment-wrap').slideDown(150);
        } else {
            $('#qe-custom-enrollment-wrap').slideUp(150);
        }
    });

    
});




