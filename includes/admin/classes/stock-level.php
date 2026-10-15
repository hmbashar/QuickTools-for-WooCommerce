<?php
/**
 * Admin Stock Level column for the products list table.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.2.0
 */

namespace QTFW\Includes\Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a sortable Stock Level badge column to the WooCommerce products table.
 */
class Stock_Level {

	/**
	 * Register product list table hooks.
	 */
	public function __construct() {
		add_filter( 'manage_edit-product_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_column_sortable' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'sort_by_stock' ) );
	}

	/**
	 * Insert the Stock Level column after the product name.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		if ( ! current_user_can( 'edit_products' ) ) {
			return $columns;
		}

		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			if ( 'name' === $key ) {
				$new_columns['qtfw_stock_level'] = __( 'Stock Level', 'quicktools-for-woocommerce' );
			}
		}

		return $new_columns;
	}

	/**
	 * Mark the column as sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function make_column_sortable( $columns ) {
		$columns['qtfw_stock_level'] = 'qtfw_stock_level';
		return $columns;
	}

	/**
	 * Output the stock badge.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Product ID.
	 */
	public function render_column( $column, $post_id ) {
		if ( 'qtfw_stock_level' !== $column || ! current_user_can( 'edit_products' ) ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			echo '&mdash;';
			return;
		}

		if ( ! $product->managing_stock() ) {
			$status = $product->get_stock_status();

			if ( 'outofstock' === $status ) {
				$tier  = 'out';
				$label = __( 'Out of stock', 'quicktools-for-woocommerce' );
			} elseif ( 'onbackorder' === $status ) {
				$tier  = 'low';
				$label = __( 'On backorder', 'quicktools-for-woocommerce' );
			} else {
				$tier  = 'unmanaged';
				$label = __( 'In stock', 'quicktools-for-woocommerce' );
			}

			$this->print_badge( $tier, $label, $label );
			return;
		}

		$qty       = (int) $product->get_stock_quantity();
		$threshold = (int) wc_get_low_stock_amount( $product );
		$formatted = number_format_i18n( $qty );

		if ( $qty <= 0 ) {
			$tier = 'out';
		} elseif ( $qty <= $threshold ) {
			$tier = 'low';
		} else {
			$tier = 'ok';
		}

		$tooltip = sprintf(
			/* translators: %s: units in stock */
			__( '%s units in stock', 'quicktools-for-woocommerce' ),
			$formatted
		);

		$this->print_badge( $tier, $formatted, $tooltip );
	}

	/**
	 * Print a badge.
	 *
	 * @param string $tier    Tier slug.
	 * @param string $text    Visible text.
	 * @param string $tooltip Tooltip text.
	 */
	private function print_badge( $tier, $text, $tooltip ) {
		printf(
			'<span class="qtfw-stock-badge qtfw-stock-badge--%1$s" title="%2$s" aria-label="%2$s">%3$s</span>',
			esc_attr( $tier ),
			esc_attr( $tooltip ),
			esc_html( $text )
		);
	}

	/**
	 * Sort the products list by stock quantity.
	 *
	 * @param \WP_Query $query Query instance.
	 */
	public function sort_by_stock( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow || 'product' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'qtfw_stock_level' !== $query->get( 'orderby' ) || ! current_user_can( 'edit_products' ) ) {
			return;
		}

		$query->set( 'meta_key', '_stock' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
