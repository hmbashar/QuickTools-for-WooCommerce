<?php
/**
 * Frontend Total Sold display.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.0.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays total sold units in product loops and on single product pages.
 */
class Total_Sold {

	/**
	 * Register storefront display hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'display_total_sold_in_loop' ), 15 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_total_sold_on_single_product' ), 25 );
	}

	/**
	 * Display total sold units in the product loop.
	 */
	public function display_total_sold_in_loop() {
		$this->render_total_sold();
	}

	/**
	 * Display total sold units on the single product page.
	 */
	public function display_total_sold_on_single_product() {
		$this->render_total_sold();
	}

	/**
	 * Shared markup for Total Sold output.
	 */
	private function render_total_sold() {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$total_sold = (int) $product->get_total_sales();

		printf(
			'<p class="qtfw-total-sold">%s</p>',
			esc_html(
				sprintf(
					/* translators: %d: number of units sold */
					__( 'Total Sold: %d', 'quicktools-for-woocommerce' ),
					$total_sold
				)
			)
		);
	}
}
