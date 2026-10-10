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
		$this->render_total_sold( 'loop' );
	}

	/**
	 * Display total sold units on the single product page.
	 */
	public function display_total_sold_on_single_product() {
		$this->render_total_sold( 'single' );
	}

	/**
	 * Shared markup for Total Sold output.
	 *
	 * @param string $context Display context: loop|single.
	 */
	private function render_total_sold( $context = 'loop' ) {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$total_sold = (int) $product->get_total_sales();
		$tier       = $this->get_sales_tier( $total_sold );
		$context    = ( 'single' === $context ) ? 'single' : 'loop';
		$formatted  = number_format_i18n( $total_sold );

		$aria = sprintf(
			/* translators: %s: formatted units sold */
			__( '%s units sold', 'quicktools-for-woocommerce' ),
			$formatted
		);

		printf(
			'<div class="qtfw-total-sold qtfw-total-sold--%1$s qtfw-total-sold--tier-%2$s" role="status" aria-label="%3$s">',
			esc_attr( $context ),
			esc_attr( $tier ),
			esc_attr( $aria )
		);

		echo '<span class="qtfw-total-sold__icon" aria-hidden="true">' . $this->get_icon_svg() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.

		echo '<span class="qtfw-total-sold__body">';
		echo '<span class="qtfw-total-sold__label">' . esc_html__( 'Total sold', 'quicktools-for-woocommerce' ) . '</span>';
		echo '<span class="qtfw-total-sold__value">' . esc_html( $formatted ) . '</span>';
		echo '</span>';

		if ( 'single' === $context && $total_sold > 0 ) {
			echo '<span class="qtfw-total-sold__meta">' . esc_html__( 'Customers chose this', 'quicktools-for-woocommerce' ) . '</span>';
		}

		echo '</div>';
	}

	/**
	 * Sales performance tier for styling.
	 *
	 * @param int $total_sold Units sold.
	 * @return string
	 */
	private function get_sales_tier( $total_sold ) {
		$total_sold = (int) $total_sold;

		if ( $total_sold <= 0 ) {
			return 'zero';
		}

		if ( $total_sold < 10 ) {
			return 'low';
		}

		if ( $total_sold < 50 ) {
			return 'mid';
		}

		return 'high';
	}

	/**
	 * Inline chart icon SVG.
	 *
	 * @return string
	 */
	private function get_icon_svg() {
		return '<svg class="qtfw-total-sold__svg" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19V5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M4 19H20" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M8 15V11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M12 15V8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/><path d="M16 15V6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>';
	}
}
