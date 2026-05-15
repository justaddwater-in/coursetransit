<?php
/**
 * Orders List View
 *
 * - Displays orders table
 * - Order details in a slide-out modal
 * - Data loaded via AJAX (coursetransit_get_orders & coursetransit_get_order_details)
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="page-header row no-gutters py-4" style="padding-top: 0 !important; padding-bottom: 0 !important;">
    <div class="col-12 col-sm-6">
        <h3 class="page-title">Orders</h3>
    </div>
</div>

<div class="card" style="max-width: 100% !important;">
    <div class="card-body p-0 mt-2 table-responsive">
        <table id="coursetransit-orders-table" class="table table-striped table-bordered w-100 mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="coursetransitOrderModal" tabindex="-1" role="dialog" aria-hidden="true"
    style="pointer-events:auto;">

    <div class="modal-dialog" style="
            position:fixed;
            top:0;
            right:0;
            margin:0;
            height:100vh;
            width:460px;
            max-width:100%;
            transform:translateX(100%);
            transition:transform .3s ease-out;
         ">

        <div class="modal-content" style="height:100%;border-radius:0;border-left:1px solid #e5e7eb;">

            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="overflow-y:auto;padding:20px;">

                <div id="coursetransit-order-loading" style="text-align:center;padding:40px 0;">
                    Loading…
                </div>

                <div id="coursetransit-order-content" class="d-none">

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Customer</div>
                        <div id="order-name" style="font-weight:600;"></div>
                        <div id="order-email" style="color:#6b7280;font-size:13px;"></div>
                        <div id="order-phone" style="color:#6b7280;font-size:13px;"></div>
                        <div id="order-gstin-wrap" style="color:#6b7280;font-size:13px; display:none;">
                            <strong>GSTIN:</strong> <span id="order-gstin"></span>
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Status</div>
                        <span id="order-status" class="badge bg-secondary"></span>
                    </div>

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Order Info</div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Order ID</span><strong id="order-id"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Payment</span><strong id="order-payment"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Transaction</span><strong id="order-transaction"></strong>
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Dates</div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Created</span><strong id="order-created"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Paid</span><strong id="order-paid"></strong>
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Billing Address</div>
                        <div id="order-billing" style="line-height:1.6;"></div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Shipping Address</div>
                        <div id="order-shipping" style="line-height:1.6;"></div>
                    </div>

                    <table class="table table-sm mb-3">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="order-items"></tbody>
                    </table>

                    <div>
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">Totals</div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Subtotal</span><strong id="total-subtotal"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Discount</span><strong id="total-discount"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Tax</span><strong id="total-tax"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span>Shipping</span><strong id="total-shipping"></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:8px;">
                            <strong>Total</strong><strong id="total-total"></strong>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>