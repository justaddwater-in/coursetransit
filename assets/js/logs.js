jQuery(function ($) {

    /**
     * LOAD LOG SETTINGS
     */
    function loadLogSettings() {

        $.get(CourseTransitLogs.ajax_url, {

            action:
                'coursetransit_get_log_settings',

            _ajax_nonce:
                CourseTransitLogs.nonce

        }).done(function (res) {

            if (!res.success) {
                return;
            }

            const enabled =
                Number(res.data.enabled) === 1 ||
                res.data.enabled === true ||
                res.data.enabled === 'yes';

            $('#cts-log-enabled').prop(
                'checked',
                enabled
            );

            toggleLogsState(enabled);

        });

    }

    /**
     * TOGGLE TABLE STATE
     */
    function toggleLogsState(enabled) {

        $('#cts-log-status-text').text(
            enabled
                ? 'Enabled'
                : 'Disabled'
        );

        if (enabled) {

            $('#cts-logs-disabled')
                .addClass('d-none');

            $('#cts-logs-wrapper')
                .removeClass('d-none');

            return;
        }

        $('#cts-logs-wrapper')
            .addClass('d-none');

        $('#cts-logs-disabled')
            .removeClass('d-none');

    }

    /**
     * SAVE TOGGLE
     */
    $(document).on(
        'change',
        '#cts-log-enabled',
        function () {

            const enabled =
                $(this).is(':checked')
                    ? 1
                    : 0;

            $.post(
                CourseTransitLogs.ajax_url,
                {

                    action:
                        'coursetransit_save_log_settings',

                    enabled: enabled,

                    _ajax_nonce:
                        CourseTransitLogs.nonce

                }

            ).done(function (res) {

                if (!res.success) {

                    Swal.fire(
                        'Error',
                        'Failed to save settings',
                        'error'
                    );

                    return;
                }

                toggleLogsState(
                    Boolean(enabled)
                );

                Swal.fire({

                    icon: 'success',

                    title: 'Settings Updated',

                    timer: 1200,

                    showConfirmButton: false

                });

            });

        }
    );

    /**
     * DATATABLE
     */
    $('#coursetransit-logs-table').DataTable({

        processing: true,

        serverSide: true,

        pageLength: 20,

        lengthChange: false,

        autoWidth: false,

        ordering: true,

        order: [[5, 'desc']],

        ajax: {

            url:
                CourseTransitLogs.ajax_url,

            type: 'GET',

            data: function (d) {

                d.action =
                    'coursetransit_logs_table';

                d._ajax_nonce =
                    CourseTransitLogs.nonce;

            }

        },

        columns: [

            {
                data: 'sr',
                orderable: false
            },

            {
                data: 'level'
            },

            {
                data: 'title'
            },

            // {
            //     data: 'source'
            // },

            {
                data: 'status'
            },

            {
                data: 'created_at'
            },

            {
                data: 'actions',
                orderable: false
            }

        ]

    });

    /**
     * INITIAL LOAD
     */
    loadLogSettings();


    $(document).on(
        'click',
        '.cts-view-log',
        function () {

            let context =
                $(this).attr('data-context');

            if (!context) {
                context = 'No context available.';
            }

            try {

                context = JSON.stringify(
                    JSON.parse(context),
                    null,
                    2
                );

            } catch (e) {
                // leave as-is
            }

            $('#cts-log-context')
                .text(context);

            $('#ctsLogViewModal')
                .modal('show');

        }
    );

});