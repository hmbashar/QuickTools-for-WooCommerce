<?php

namespace QTFW\Includes\Admin\Classes\Columns;

class PreviousOrdersColumn {

    public function __construct() {
        add_filter('manage_edit-shop_order_columns', [$this, 'add_previous_orders_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_previous_orders_column'], 10, 2);
        add_action('admin_head', [$this, 'add_tooltip_styles']);
    }

    /**
     * Add a new column to the WooCommerce Orders list.
     *
     * @param array $columns The existing columns.
     * @return array Modified columns.
     */
    public function add_previous_orders_column($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            // Add the new column after the "Order Total" column.
            if ('order_total' === $key) {
                $new_columns['previous_orders'] = __('Previous Orders', 'quicktools-for-woocommerce');
            }
        }

        return $new_columns;
    }

    /**
     * Render the data for the new column.
     *
     * @param string $column The column being rendered.
     * @param int $post_id The order ID.
     */
    public function render_previous_orders_column($column, $post_id) {
        if ('previous_orders' === $column) {
            $order = wc_get_order($post_id);
            $billing_phone = $order->get_billing_phone();

            if ($billing_phone) {
                $order_counts = $this->get_orders_by_status_counts($billing_phone);

                // Render small circles with tooltips.
                $this->render_tooltip_circles($order_counts);
            } else {
                echo __('No Phone Number', 'quicktools-for-woocommerce');
            }
        }
    }

    /**
     * Get the count of orders by status for a specific phone number.
     *
     * @param string $phone_number The billing phone number.
     * @return array Counts of orders by status.
     */
    private function get_orders_by_status_counts($phone_number) {     
        try {
            if ($this->is_hpos_enabled()) {  
                return $this->get_orders_by_status_counts_hpos($phone_number);
            } else {               
                return $this->get_orders_by_status_counts_legacy($phone_number);
            }
        } catch (\Exception $e) {
            error_log('Error fetching orders: ' . $e->getMessage());
            return [
                'total' => 0,
                'processing' => 0,
                'on-hold' => 0,
                'pending' => 0,
                'cancelled' => 0,
            ];
        }
    }

    /**
     * Get the count of orders by status for legacy storage.
     *
     * @param string $phone_number The billing phone number.
     * @return array Counts of orders by status.
     */
    private function get_orders_by_status_counts_legacy($phone_number) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT p.post_status, COUNT(*) as count
             FROM {$wpdb->prefix}postmeta pm
             INNER JOIN {$wpdb->prefix}posts p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_billing_phone'
             AND pm.meta_value = %s
             AND p.post_type = 'shop_order'
             GROUP BY p.post_status",
            $phone_number
        );

        $results = $wpdb->get_results($query);

        // Initialize status counts.
        $statuses = [
            'total' => 0,
            'processing' => 0,
            'on-hold' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];

        foreach ($results as $row) {
            $statuses['total'] += $row->count;

            switch ($row->post_status) {
                case 'wc-processing':
                    $statuses['processing'] += $row->count;
                    break;
                case 'wc-on-hold':
                    $statuses['on-hold'] += $row->count;
                    break;
                case 'wc-pending':
                    $statuses['pending'] += $row->count;
                    break;
                case 'wc-cancelled':
                    $statuses['cancelled'] += $row->count;
                    break;
            }
        }

        return $statuses;
    }

    /**
     * Get the count of orders by status for HPOS.
     *
     * @param string $phone_number The billing phone number.
     * @return array Counts of orders by status.
     */
    private function get_orders_by_status_counts_hpos($phone_number) {
        global $wpdb;
    
        $orders_table = $wpdb->prefix . 'wc_orders';
        $postmeta_table = $wpdb->prefix . 'postmeta';
    
        // Query to fetch orders with matching phone numbers
        $query = $wpdb->prepare(
            "SELECT o.status, COUNT(*) as count
             FROM {$orders_table} o
             INNER JOIN {$postmeta_table} pm ON o.id = pm.post_id
             WHERE pm.meta_key = '_billing_phone'
             AND pm.meta_value = %s
             GROUP BY o.status",
            $phone_number
        );
    
        $results = $wpdb->get_results($query);
    
        // Debugging: Log the query and results
        error_log('HPOS Query: ' . $wpdb->last_query);
        error_log('HPOS Results: ' . print_r($results, true));
    
        // Initialize status counts
        $statuses = [
            'total' => 0,
            'processing' => 0,
            'on-hold' => 0,
            'pending' => 0,
            'cancelled' => 0,
        ];
    
        if (!empty($results)) {
            foreach ($results as $row) {
                $statuses['total'] += $row->count;
    
                switch ($row->status) {
                    case 'processing':
                        $statuses['processing'] += $row->count;
                        break;
                    case 'on-hold':
                        $statuses['on-hold'] += $row->count;
                        break;
                    case 'pending':
                        $statuses['pending'] += $row->count;
                        break;
                    case 'cancelled':
                        $statuses['cancelled'] += $row->count;
                        break;
                }
            }
        } else {
            error_log('No results found for phone number: ' . $phone_number);
        }
    
        return $statuses;
    }
    
    
    

    /**
     * Check if HPOS is enabled.
     *
     * @return bool True if HPOS is enabled, false otherwise.
     */
    private function is_hpos_enabled() {
        try {
            if (class_exists(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)) {
                $controller = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class);
                return method_exists($controller, 'is_enabled') && $controller->is_enabled();
            }
        } catch (\Exception $e) {
            error_log('Error checking HPOS status: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Render small circles with tooltips.
     *
     * @param array $counts Counts of orders by status.
     */
    private function render_tooltip_circles($counts) {
        $statuses = [
            'Total' => ['count' => $counts['total'], 'color' => '#aaa'],
            'Processing' => ['count' => $counts['processing'], 'color' => '#28a745'],
            'On Hold' => ['count' => $counts['on-hold'], 'color' => '#17a2b8'],
            'Pending' => ['count' => $counts['pending'], 'color' => '#ffc107'],
            'Cancelled' => ['count' => $counts['cancelled'], 'color' => '#dc3545'],
        ];

        foreach ($statuses as $label => $status) {
            echo '<div class="tooltip-circle" style="background-color:' . esc_attr($status['color']) . ';"
                 data-tooltip="' . esc_attr($label . ': ' . $status['count']) . '">
                 ' . esc_html($status['count']) . '
                 </div>';
        }
    }

    /**
     * Add CSS styles for tooltips.
     */
    public function add_tooltip_styles() {
        ?>
        <style>
            .tooltip-circle {
                display: inline-block;
                width: 25px;
                height: 25px;
                border-radius: 50%;
                color: #fff;
                text-align: center;
                line-height: 25px;
                font-size: 12px;
                margin-right: 5px;
                position: relative;
                cursor: pointer;
            }
            .tooltip-circle:hover::after {
                content: attr(data-tooltip);
                position: absolute;
                bottom: 150%;
                left: 50%;
                transform: translateX(-50%);
                white-space: nowrap;
                background-color: #000;
                color: #fff;
                padding: 5px 8px;
                border-radius: 4px;
                font-size: 11px;
                z-index: 1000;
            }
            .tooltip-circle:hover::before {
                content: '';
                position: absolute;
                bottom: 120%;
                left: 50%;
                transform: translateX(-50%);
                border: 5px solid transparent;
                border-top-color: #000;
                z-index: 1000;
            }
        </style>
        <?php
    }
}