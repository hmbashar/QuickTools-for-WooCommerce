<?php
/**
 * Low stock email alert.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.5.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emails the store admin once when a product falls to its low-stock threshold.
 */
class Low_Stock_Email {

	/**
	 * Product meta flagging that an alert was already sent.
	 *
	 * @var string
	 */
	const NOTIFIED_META = '_qtfw_low_stock_notified';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_set_stock', array( $this, 'maybe_notify' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'maybe_notify' ) );
	}

	/**
	 * Send an alert when stock reaches the threshold; reset once restocked.
	 *
	 * @param \WC_Product $product Product whose stock changed.
	 */
	public function maybe_notify( $product ) {
		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
			return;
		}

		$qty = $product->get_stock_quantity();

		if ( null === $qty ) {
			return;
		}

		$qty       = (int) $qty;
		$threshold = (int) wc_get_low_stock_amount( $product );
		$notified  = (bool) $product->get_meta( self::NOTIFIED_META, true );

		if ( $qty > $threshold ) {
			if ( $notified ) {
				$product->delete_meta_data( self::NOTIFIED_META );
				$product->save_meta_data();
			}
			return;
		}

		if ( $notified ) {
			return;
		}

		$product->update_meta_data( self::NOTIFIED_META, 1 );
		$product->save_meta_data();

		$this->send( $product, $qty, $threshold );
	}

	/**
	 * Send the email.
	 *
	 * @param \WC_Product $product   Product.
	 * @param int         $qty       Current stock.
	 * @param int         $threshold Low-stock threshold.
	 */
	private function send( $product, $qty, $threshold ) {
		$recipient = apply_filters( 'qtfw_low_stock_email_recipient', get_option( 'admin_email' ), $product );

		if ( ! is_email( $recipient ) ) {
			return;
		}

		$name = wp_strip_all_tags( $product->get_formatted_name() );

		$subject = sprintf(
			/* translators: 1: site name, 2: product name */
			__( '[%1$s] Low stock: %2$s', 'quicktools-for-woocommerce' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$name
		);

		$message = sprintf(
			/* translators: 1: product name, 2: stock quantity, 3: low-stock threshold, 4: edit link */
			__( "%1\$s is running low.\n\nIn stock: %2\$d\nLow-stock threshold: %3\$d\n\nEdit product: %4\$s", 'quicktools-for-woocommerce' ),
			$name,
			$qty,
			$threshold,
			admin_url( 'post.php?post=' . ( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) . '&action=edit' )
		);

		wp_mail( $recipient, $subject, $message );
	}
}
