jQuery(function ($) {



    /* =========================================================
     * DATATABLE
     * =======================================================*/
    const table = $('#coursetransit-instructors-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        autoWidth: false,
        ordering: true,
        language: { search: '' },

        order: [[2, 'asc']], // name column

        ajax: {
            url: CourseTransitInstructors.ajax_url,
            type: 'GET',
            data: function (d) {
                d.action = 'coursetransit_instructors_table';
                d._ajax_nonce = CourseTransitInstructors.nonce;
            }
        },

        columns: [
            { data: 'sr', orderable: false, defaultContent: '' },
            { data: 'avatar', orderable: false, defaultContent: '' },
            { data: 'name', defaultContent: '' },
            { data: 'email', defaultContent: '' },
            { data: 'courses', defaultContent: '' },
            // { data: 'status', defaultContent: '' },
            { data: 'public', defaultContent: '' },
            {
                data: 'updated_at',
                defaultContent: '',
                className: 'dt-left'
            },
            { data: 'actions', orderable: false, defaultContent: '' }
        ]
    });

    /* =========================================================
     * DELETE INSTRUCTOR
     * =======================================================*/
    $(document).on('click', '.delete-instructor', function () {

        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete Instructor?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:4px;">delete</span> Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                confirmButton: 'button button-primary delete_instructor px-4',
                cancelButton: 'button button-secondary mx-2',
            },
            buttonsStyling: false
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post(CourseTransitInstructors.ajax_url, {
                action: 'coursetransit_delete_instructor',
                _ajax_nonce: CourseTransitInstructors.nonce,
                id: id
            }).done(res => {

                if (!res.success) {
                    Swal.fire('Error', res?.data?.message || 'Delete failed', 'error');
                    return;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Deleted',
                    timer: 1200,
                    showConfirmButton: false
                });

                table.ajax.reload(null, false);
            });
        });
    });

    /* =========================================================
     * FIX WIDTH (Shards layout bug)
     * =======================================================*/
    table.on('draw', function () {
        table.columns.adjust();
    });



    /* =========================================================
     * SEARCH STYLE
     * =======================================================*/
    $('#coursetransit-instructors-table').on('init.dt', function () {

        const $input = $('#coursetransit-instructors-table_filter input');

        $input
            .addClass('form-control')
            .attr('placeholder', 'Search instructors…');
    });

    // SLIDE IN
    $('#coursetransitInstructorModal').on('shown.bs.modal', function () {
        $(this).find('.modal-dialog').css('transform', 'translateX(0)');
    });

    // SLIDE OUT
    $('#coursetransitInstructorModal').on('hide.bs.modal', function () {
        $(this).find('.modal-dialog').css('transform', 'translateX(100%)');
    });

    function loadCourses(selected = []) {

        $.get(CourseTransitInstructors.ajax_url, {
            action: 'coursetransit_get_courses',
            _ajax_nonce: CourseTransitInstructors.nonce,
            
        }, function (res) {

            if (!res.success) return;

            const $select = $('#ins-courses');
            $select.empty();

            res.data.forEach(c => {
                const option = new Option(c.text, c.id, false, selected.includes(String(c.id)));
                $select.append(option);
            });

            $select.trigger('change');
        });
    }

    $('#ins-courses').select2({
        placeholder: 'Select courses',
        width: '100%'
    });

    $('#coursetransit-save-instructor').on('click', function () {

        const name = $('input[name="name"]').val().trim();
        const email = $('input[name="email"]').val().trim();
        const headline = $('input[name="headline"]').val().trim();
        const bio = $('textarea[name="bio"]').val().trim();
        const courses = $('#ins-courses').val();

        if (!name) return Swal.fire('Required', 'Name is required', 'warning');
        // if (!email) return Swal.fire('Required', 'Email is required', 'warning');
        // if (!headline) return Swal.fire('Required', 'Headline is required', 'warning');
        // if (!bio) return Swal.fire('Required', 'Bio is required', 'warning');
        // if (!courses || !courses.length) return Swal.fire('Required', 'Select at least one course', 'warning');

        const form = $('#coursetransit-instructor-form');
        const data = form.serialize();

        $.post(CourseTransitInstructors.ajax_url,
            data + '&action=coursetransit_save_instructor&_ajax_nonce=' + CourseTransitInstructors.nonce
        ).done(res => {

            if (!res.success) {
                Swal.fire('Error', res?.data?.message || 'Save failed', 'error');
                return;
            }

            Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });

            $('#coursetransitInstructorModal').modal('hide');
            table.ajax.reload(null, false);
        });
    });


    $('#coursetransit-add-instructor').on('click', function () {

        $('#coursetransit-instructor-modal-title').text('Add Instructor');
        $('#coursetransit-instructor-form')[0].reset();
        $('#ins-id').val('');
        $('#ins-avatar').val('');

        $('#ins-avatar-preview').attr('src', CourseTransitInstructors.default_avatar);

        $('#ins-courses').val(null).trigger('change');
        loadCourses([]);

        $('#coursetransit-instructor-form-wrap').removeClass('d-none');
        $('#coursetransit-instructor-courses').addClass('d-none');
        $('#coursetransit-instructor-loading').hide();
        $('#coursetransit-save-instructor').show();

        bootstrap.Modal.getOrCreateInstance(
            $('#coursetransitInstructorModal')[0]
        ).show();
    });

    $('#coursetransitInstructorModal').on('hidden.bs.modal', function () {
        resetInstructorModal();
    });

    function resetInstructorModal() {

        const form = $('#coursetransit-instructor-form');

        if (form.length) form[0].reset();

        $('#ins-id').val('');

        // Avatar reset
        $('#ins-avatar').val('');
        $('#ins-avatar-preview').attr('src', CourseTransitInstructors.default_avatar);

        // Select2 reset
        $('#ins-courses').val(null).trigger('change');

        // Loader hide
        $('#coursetransit-instructor-loading').hide();

        // Show FORM, hide COURSES
        $('#coursetransit-instructor-form-wrap').removeClass('d-none');
        $('#coursetransit-instructor-courses').addClass('d-none');
    }




    /* =========================================================
    * AVATAR UPLOAD (WP MEDIA PICKER)
    * =======================================================*/
    let mediaUploader;

    $('#ins-upload-avatar').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Avatar',
            button: { text: 'Use Image' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();

            $('#ins-avatar').val(attachment.url);
            $('#ins-avatar-preview').attr('src', attachment.url);
        });

        mediaUploader.open();
    });

    /* =========================================================
    * EDIT → LOAD INSTRUCTOR INTO MODAL
    * =======================================================*/
    $(document).on('click', '.edit-instructor', function () {

        const id = $(this).data('id');

        $.get(CourseTransitInstructors.ajax_url, {
            action: 'coursetransit_get_instructor',
            id: id,
            _ajax_nonce: CourseTransitInstructors.nonce
        }, function (res) {

            if (!res.success) {
                Swal.fire('Error', res?.data?.message || 'Failed to load', 'error');
                return;
            }

            const i = res.data;

            $('#coursetransit-instructor-modal-title').text('Edit Instructor');

            $('#ins-id').val(i.id);
            $('input[name="name"]').val(i.name);
            $('input[name="email"]').val(i.email);
            $('input[name="headline"]').val(i.headline);
            $('textarea[name="bio"]').val(i.bio);
            $('input[name="focus_areas"]').val(i.focus_areas);
            $('input[name="expertise"]').val(i.expertise);
            $('select[name="is_active"]').val(i.is_active);
            $('select[name="is_public"]').val(i.is_public);

            // Avatar
            if (i.avatar) {
                $('#ins-avatar-preview').attr('src', i.avatar);
                $('#ins-avatar').val(i.avatar);
            } else {
                $('#ins-avatar-preview').attr('src', CourseTransitInstructors.default_avatar);
                $('#ins-avatar').val('');
            }

            // Load courses + preselect
            loadCourses(i.courses.map(String));
            $('#coursetransit-instructor-form-wrap').removeClass('d-none');
            $('#coursetransit-instructor-courses').addClass('d-none');
            $('#coursetransit-instructor-loading').hide();
            $('#coursetransit-save-instructor').show();

            bootstrap.Modal.getOrCreateInstance(
                $('#coursetransitInstructorModal')[0]
            ).show();
        });
    });

    $(document).on('click', '.view-instructor-courses', function (e) {
        e.preventDefault();

        const id = $(this).data('id');

        $('#coursetransit-instructor-loading').show();

        $('#coursetransit-instructor-form-wrap').addClass('d-none');   // hide form
        $('#coursetransit-instructor-courses').removeClass('d-none');  // show courses
        $('#coursetransit-instructor-modal-title').text('Instructor Courses');
        $('#coursetransit-save-instructor').hide();

        $('#coursetransit-instructor-courses-list').html('');

        bootstrap.Modal.getOrCreateInstance(
            $('#coursetransitInstructorModal')[0]
        ).show();

        $.get(CourseTransitInstructors.ajax_url, {
            action: 'coursetransit_instructor_courses',
            id: id,
            _ajax_nonce: CourseTransitInstructors.nonce
        })
            .done(res => {

                $('#coursetransit-instructor-loading').hide();

                const list = $('#coursetransit-instructor-courses-list');
                list.empty();

                // Handle failed response
                if (!res || !res.success) {
                    list.html(`
                    <div class="text-danger">
                        Failed to load instructor courses.
                    </div>
                `);
                    return;
                }

                const courses = res?.data?.courses || [];

                // No courses case
                if (!courses.length) {
                    list.html(`
                        <div class="text-muted">
                            No courses assigned
                        </div>
                    `);
                    return;
                }

                // Render courses
                const html = courses.map(c => `
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <img
                        src="${c.course_image_url || CourseTransitInstructors.default_avatar}"
                        alt="${c.fullname || 'Course'}"
                        style="width:44px;height:44px;border-radius:6px;object-fit:cover;"
                    >
                    <div>
                        <div style="font-weight:600;" class="ct-font">
                            ${c.fullname || 'Untitled Course'}
                        </div>
                        <div style="font-size:12px;color:#6b7280;">
                            ${c.shortname || ''}
                        </div>
                    </div>
                </div>
            `).join('');

                list.html(html);
            })
            .fail(xhr => {
                console.error('Instructor Courses AJAX Failed:', xhr.responseText);

                $('#coursetransit-instructor-loading').hide();

                $('#coursetransit-instructor-courses-list').html(`
                    <div class="text-danger">
                        Unable to load courses. Please try again.
                    </div>
                `);
            });
    });

});


