jQuery(function ($) {

    /**
     * PRO FEATURE PROMOTIONS
     */
    $(document).on('click', '.coursetransit-pro-feature-trigger[data-pro-feature="request-quote"]', function (e) {

        e.preventDefault();

        Swal.fire({
            icon: 'info',
            title: 'Unlock Request a Quote',
            html: `
                <div class="ct-pro-promo-content">
                    <p>
                        <strong>Request a Quote</strong> is available in <strong>CourseTransit Pro</strong>.
                    </p>
                    <p style="margin-bottom:10px;">
                        Upgrade to Pro to:
                    </p>
                    <ul style="padding-left:20px;margin:0;">
                        <li>Let customers request a custom quote for multiple seats</li>
                        <li>Support bulk purchasing and quantity-based pricing</li>
                        <li>Collect and manage quote requests from your WordPress admin</li>
                        <li>Convert accepted quotes into WooCommerce orders</li>
                    </ul>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Upgrade to Pro',
            cancelButtonText: 'Maybe Later',
            confirmButtonColor: '#2271b1',
            reverseButtons: true,
            customClass: {
                popup: 'ct-pro-promo',
                confirmButton: 'button button-primary px-4',
                cancelButton: 'button button-secondary mx-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.open('https://justaddwater.in/products/coursetransit-wordpress-moodle-integration/#pricing', '_blank');
            }
        });

    });


    const table = $('#coursetransit-orders-table').DataTable({
        processing: true,
        serverSide: false,
        pageLength: 10,
        searching: true,
        lengthChange: false,
        autoWidth: false,
        ordering: true,
        language: {
            search: ''
        },
        order: [[5, 'desc']],
        ajax: {
            url: CourseTransitAjax.ajax_url,
            type: 'GET',
            dataSrc: 'data',
            data: {
                action: 'coursetransit_orders_table',
                _ajax_nonce: CourseTransitAjax.nonce
            }
        },
        columns: [
            { data: 'order_id' },
            { data: 'customer' },
            { data: 'email' },
            { data: 'total' },
            { data: 'status' },
            {
                data: 'date',
                type: 'string',
                className: 'dt-left'
            },
            { data: 'actions', orderable: false }
        ]
    });

    table.on('draw', function () {
        table.columns.adjust();
    });

    $('#coursetransit-orders-table').on('init.dt', function () {
        const $input = $('#coursetransit-orders-table_filter input');

        $input
            .addClass('form-control')
            .attr('placeholder', 'Search orders…');
    });

    $(document).on('click', '.view-order', function () {

        const orderId = $(this).data('id');

        $('#coursetransit-order-loading').show();
        $('#coursetransit-order-content').addClass('d-none');

        bootstrap.Modal.getOrCreateInstance(
            $('#coursetransitOrderModal')[0]
        ).show();

        $.get(CourseTransitAjax.ajax_url, {
            action: 'coursetransit_order_details',
            _ajax_nonce: CourseTransitAjax.nonce,
            id: orderId
        }).done(res => {

            $('#coursetransit-order-loading').hide();
            if (!res.success) return;

            const o = res.data.order;

            $('#order-name').text(o.customer.name);
            $('#order-email').text(o.customer.email);
            $('#order-phone').text(o.customer.phone || '—');
            if (o.customer.gstin) {
                $('#order-gstin').text(o.customer.gstin);
                $('#order-gstin-wrap').show();
            } else {
                $('#order-gstin-wrap').hide();
            }

            // Use the exact same badge markup/classes as the orders table.
            $('#order-status').html(o.status_badge);
            $('#order-id').html(`
                <a href="${o.edit_link}" target="_blank">#${o.id}</a>
            `);
            $('#order-payment').text(o.payment.method);
            $('#order-transaction').text(o.payment.transaction);

            $('#order-created').text(o.dates.created);
            $('#order-paid').text(o.dates.paid);

            $('#order-billing').html(o.addresses.billing);
            $('#order-shipping').html(o.addresses.shipping);

            const tbody = $('#order-items').empty();
            o.items.forEach(item => {
                tbody.append(`
                    <tr>
                        <td>${item.name}</td>
                        <td class="text-end">${item.qty}</td>
                        <td class="text-end">${item.total}</td>
                    </tr>
                `);
            });

            $('#total-subtotal').html(o.totals.subtotal);
            $('#total-discount').html(o.totals.discount);
            $('#total-tax').html(o.totals.tax);
            $('#total-shipping').html(o.totals.shipping);
            $('#total-total').html(o.totals.total);

            $('#coursetransit-order-content').removeClass('d-none');
        });
    });

    // SLIDE IN
    $('#coursetransitOrderModal').on('shown.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(0)');
    });

    // SLIDE OUT
    $('#coursetransitOrderModal').on('hide.bs.modal', function () {
        $(this).find('.modal-dialog')
            .css('transform', 'translateX(100%)');
    });

});
