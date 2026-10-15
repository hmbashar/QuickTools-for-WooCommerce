<?php
/**
 * Frontend "Only X left" urgency badge.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.4.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows an "Only X left" badge when stock of a product is running low.
 */
class Only_Left {

	/**
	 * Minimum threshold below which the badge appears.
	 *
	 * @var int
	 */
	const MIN_THRESHOLD = 5;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'render' ), 17 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 27 );
	}

	/**
	 * Output the badge for the current product.
	 */
	public function render() {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() || $product->backorders_allowed() ) {
			return;
		}

		$qty = $product->get_stock_quantity();

		if ( null === $qty || (int) $qty < 1 ) {
			return;
		}

		$qty       = (int) $qty;
		$threshold = (int) apply_filters(
			'qtfw_only_left_threshold',
			max( self::MIN_THRESHOLD, (int) wc_get_low_stock_amount( $product ) ),
			$product
		);

		if ( $qty > $threshold ) {
			return;
		}

		$text = sprintf(
			/* translators: %s: formatted number of units left in stock */
			_n( 'Only %s left in stock', 'Only %s left in stock', $qty, 'quicktools-for-woocommerce' ),
			number_format_i18n( $qty )
		);

		echo '<div class="qtfw-only-left" role="status">' . esc_html( $text ) . '</div>';
	}
}
