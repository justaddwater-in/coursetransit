<?php

namespace CourseTransit\Controllers;

use WC_Order_Query;

class OrdersController extends BaseController
{
    public function index()
    {
        if (!class_exists('WooCommerce')) {
            wp_die('WooCommerce is not active.');
        }

        $this->render('orders/index');
    }
    public function table()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => esc_html__(
                    'Unauthorized access.',
                    'coursetransit'
                )
            ], 403);
        }

        $query = new WC_Order_Query([
            'limit' => 150, //  load latest 150 orders 
            'status' => ['completed', 'processing', 'on-hold', 'failed'],
            'return' => 'objects',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $orders = $query->get_orders();
        $data = [];

        foreach ($orders as $order) {

            if (!$this->isCourseTransitOrder($order)) {
                continue;
            }
            $data[] = [
                'order_id' => '<a href="' . esc_url(
                    admin_url(
                        'post.php?post=' . $order->get_id() . '&action=edit'
                    )
                ) . '" target="_blank">
                    #' . $order->get_id() . '
                </a>',
                'customer' => $order->get_formatted_billing_full_name(),
                'email' => $order->get_billing_email(),
                'total' => wc_price($order->get_total()),
                'status' => (function () use ($order) {

                    $status = $order->get_status();

                    $map = [
                        'completed' => '<span class="badge badge-success">Completed</span>',
                        'processing' => '<span class="badge badge-primary">Processing</span>',
                        'on-hold' => '<span class="badge badge-warning">On Hold</span>',
                        'failed' => '<span class="badge badge-danger">Failed</span>',
                        'pending' => '<span class="badge badge-secondary">Pending</span>',
                        'cancelled' => '<span class="badge badge-dark">Cancelled</span>',
                        'refunded' => '<span class="badge badge-info">Refunded</span>',
                    ];

                    return $map[$status] ?? '<span class="badge badge-light">' . esc_html(ucfirst($status)) . '</span>';

                })(),
                'date' => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'actions' => '<div class="coursetransit-actions" style=" display:flex; gap:6px; align-items:center;">
                                <button
                                    class="button button-small view-order" style="
                            border-radius:4px;
                            padding:2px 10px;
                            display:flex;
                            align-items:center;
                            gap:4px;
                    "
                                    data-id="' . esc_attr($order->get_id()) . '"
                                    title="View Order">
                                    <span class="material-icons" style="font-size:16px;">visibility</span>
                                    View
                                </button>
                                </div>',
            ];
        }

        wp_send_json([
            'data' => $data,
        ]);
    }
    public function details()
    {
        check_ajax_referer('coursetransit_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => esc_html__(
                    'Unauthorized access.',
                    'coursetransit'
                )
            ], 403);
        }

        $order_id = isset($_GET['id'])
            ? absint(wp_unslash($_GET['id']))
            : 0;

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error([
                'message' => esc_html__(
                    'Invalid order.',
                    'coursetransit'
                )
            ], 400);
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'name' => $item->get_name(),
                'qty' => $item->get_quantity(),
                'total' => wc_price($item->get_total()),
            ];
        }

        wp_send_json_success([
            'order' => [
                'id' => $order->get_id(),
                'status' => wc_get_order_status_name($order->get_status()),
                'customer' => [
                    'name' => $order->get_formatted_billing_full_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'gstin' => $order->get_meta('_billing_gstin') ?: '',
                ],
                'payment' => [
                    'method' => $order->get_payment_method_title() ?: '—',
                    'transaction' => $order->get_transaction_id() ?: '—',
                ],
                'dates' => [
                    'created' => $order->get_date_created()?->date('Y-m-d H:i'),
                    'paid' => $order->get_date_paid()?->date('Y-m-d H:i'),
                ],
                'addresses' => [
                    'billing' => $order->get_formatted_billing_address(),
                    'shipping' => $order->get_formatted_shipping_address(),
                ],
                'totals' => [
                    'subtotal' => wc_price($order->get_subtotal()),
                    'discount' => wc_price($order->get_discount_total()),
                    'tax' => wc_price($order->get_total_tax()),
                    'shipping' => wc_price($order->get_shipping_total()),
                    'total' => wc_price($order->get_total()),
                ],
                'items' => $items,
                'coursetransit' => [
                    'enrolled' => (bool) $order->get_meta('_coursetransit_enrolled'),
                ],
                'edit_link' => admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
            ]
        ]);
    }

    protected function isCourseTransitOrder($order): bool
    {
        foreach ($order->get_items() as $item) {

            $product_id = $item->get_product_id();

            if (!$product_id) {
                continue;
            }

            $moodle_id = get_post_meta(
                $product_id,
                '_coursetransit_moodle_id',
                true
            );

            if (!empty($moodle_id)) {
                return true;
            }
        }

        return false;
    }
}
