<?php

namespace QTFW\Includes\Admin\Classes\Columns;

class PreviousOrdersColumn {

    public function __construct() {
        add_filter('manage_edit-shop_order_columns', [$this, 'add_previous_orders_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_previous_orders_column'], 10, 2);
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
                $new_columns['previous_orders'] = __('Previous Orders', 'qtfw');
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
            $customer_id = $order->get_customer_id();

            if ($customer_id) {
                $order_count = wc_get_customer_order_count($customer_id) - 1; // Exclude the current order.
                echo $order_count > 0 ? esc_html($order_count) : __('None', 'qtfw');
            } else {
                echo __('Guest', 'qtfw');
            }
        }
    }
}