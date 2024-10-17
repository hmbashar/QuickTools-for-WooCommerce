<?php
namespace QTFW\Includes\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class Total_Sold {

    /**
     * The constructor method.
     *
     * Adds hooks to display the total sold units on the archive page and single product page.
     */
    public function __construct() {
        // Hook to display the total sold in product loops (archive pages)
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'display_total_sold_in_loop'], 15);

        // Hook to display total sold on the single product page
        add_action('woocommerce_single_product_summary', [$this, 'display_total_sold_on_single_product'], 25);
    }

    /**
     * Display total sold units in the WooCommerce product loop (archive pages).
     */
    public function display_total_sold_in_loop() {
        global $product;

        $total_sold = $product->get_total_sales();

        echo '<p class="qtfw-total-sold">' . esc_html__('Total Sold: ', 'quicktools-for-woocommerce') . esc_html($total_sold ? $total_sold : '0') . '</p>';
    }

    /**
     * Display total sold units on the single product page.
     */
    public function display_total_sold_on_single_product() {
        global $product;

        $total_sold = $product->get_total_sales();

        echo '<p class="qtfw-total-sold">' . esc_html__('Total Sold: ', 'quicktools-for-woocommerce') . esc_html($total_sold ? $total_sold : '0') . '</p>';
    }
}
