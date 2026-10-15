<?php
/**
 * Frontend sale countdown.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.7.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows a live countdown for scheduled sales that are about to end.
 */
class Sale_Countdown {

	/**
	 * Default window (seconds) before the sale end in which the countdown shows.
	 *
	 * @var int
	 */
	const DEFAULT_WINDOW = 7 * DAY_IN_SECONDS;

	/**
	 * Whether the script was enqueued.
	 *
	 * @var bool
	 */
	private $enqueued = false;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'render' ), 18 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 11 );
	}

	/**
	 * Output the countdown for the current product.
	 */
	public function render() {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_on_sale() ) {
			return;
		}

		$end = $product->get_date_on_sale_to();

		if ( ! $end instanceof \WC_DateTime ) {
			return;
		}

		$remaining = $end->getTimestamp() - time();
		$window    = (int) apply_filters( 'qtfw_sale_countdown_window', self::DEFAULT_WINDOW, $product );

		if ( $remaining <= 0 || $remaining > $window ) {
			return;
		}

		$this->enqueue_script();

		printf(
			'<div class="qtfw-countdown" data-end="%1$d" role="timer"><span class="qtfw-countdown__label">%2$s</span> <span class="qtfw-countdown__time">%3$s</span></div>',
			(int) $end->getTimestamp(),
			esc_html__( 'Sale ends in', 'quicktools-for-woocommerce' ),
			esc_html( human_time_diff( time(), $end->getTimestamp() ) )
		);
	}

	/**
	 * Enqueue the countdown script once.
	 */
	private function enqueue_script() {
		if ( $this->enqueued ) {
			return;
		}

		$this->enqueued = true;

		wp_enqueue_script(
			'qtfw-countdown',
			QTFW_PLUGIN_URL . 'assets/js/sale-countdown.js',
			array(),
			QTFW_VERSION,
			true
		);

		wp_localize_script(
			'qtfw-countdown',
			'qtfwCountdown',
			array(
				'days'    => __( 'd', 'quicktools-for-woocommerce' ),
				'hours'   => __( 'h', 'quicktools-for-woocommerce' ),
				'minutes' => __( 'm', 'quicktools-for-woocommerce' ),
				'seconds' => __( 's', 'quicktools-for-woocommerce' ),
				'ended'   => __( 'Sale ended', 'quicktools-for-woocommerce' ),
			)
		);
	}
}
