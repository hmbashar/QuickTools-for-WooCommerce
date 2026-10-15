<?php
/**
 * Quick copy of product ID and SKU on the products list.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.8.0
 */

namespace QTFW\Includes\Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds "Copy ID" and "Copy SKU" row actions to the products list.
 */
class Quick_Copy {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );
	}

	/**
	 * Append copy actions to product rows.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Post object.
	 * @return array
	 */
	public function add_row_actions( $actions, $post ) {
		if ( ! $post instanceof \WP_Post || 'product' !== $post->post_type || ! current_user_can( 'edit_products' ) ) {
			return $actions;
		}

		$actions['qtfw_copy_id'] = $this->link( (string) $post->ID, __( 'Copy ID', 'quicktools-for-woocommerce' ) );

		$product = wc_get_product( $post->ID );

		if ( $product instanceof \WC_Product && '' !== (string) $product->get_sku() ) {
			$actions['qtfw_copy_sku'] = $this->link( (string) $product->get_sku(), __( 'Copy SKU', 'quicktools-for-woocommerce' ) );
		}

		return $actions;
	}

	/**
	 * Build a copy link.
	 *
	 * @param string $value Value to copy.
	 * @param string $label Link label.
	 * @return string
	 */
	private function link( $value, $label ) {
		return sprintf(
			'<a href="#" class="qtfw-copy" data-copy="%1$s" data-copied="%3$s">%2$s</a>',
			esc_attr( $value ),
			esc_html( $label ),
			esc_attr__( 'Copied!', 'quicktools-for-woocommerce' )
		);
	}
}
