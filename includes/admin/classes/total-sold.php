<?php
/**
 * Admin Sold column for the products list table.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.0.0
 */

namespace QTFW\Includes\Admin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a sortable Sold column to the WooCommerce products table.
 */
class Total_Sold {

	/**
	 * Register product list table hooks.
	 */
	public function __construct() {
		add_filter( 'manage_edit-product_columns', array( $this, 'add_sold_column_to_product_table' ) );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'make_sold_column_sortable_in_product_table' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'populate_sold_column_in_product_table' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'custom_sorting_for_sold_column' ) );
	}

	/**
	 * Insert the Sold column after the product name.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_sold_column_to_product_table( $columns ) {
		if ( ! current_user_can( 'edit_products' ) ) {
			return $columns;
		}

		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			if ( 'name' === $key ) {
				$new_columns['product_sold'] = __( 'Sold', 'quicktools-for-woocommerce' );
			}
		}

		return $new_columns;
	}

	/**
	 * Mark the Sold column as sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function make_sold_column_sortable_in_product_table( $columns ) {
		$columns['product_sold'] = 'product_sold';
		return $columns;
	}

	/**
	 * Output total sales for the Sold column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Product ID.
	 */
	public function populate_sold_column_in_product_table( $column, $post_id ) {
		if ( 'product_sold' !== $column ) {
			return;
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}

		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			echo '&mdash;';
			return;
		}

		$total_sold = (int) $product->get_total_sales();
		$tier       = $this->get_sales_tier( $total_sold );
		$formatted  = number_format_i18n( $total_sold );

		$tooltip = sprintf(
			/* translators: %s: formatted units sold */
			__( '%s units sold', 'quicktools-for-woocommerce' ),
			$formatted
		);

		printf(
			'<span class="qtfw-sold-badge qtfw-sold-badge--%1$s" data-tooltip="%2$s" title="%2$s" aria-label="%2$s">',
			esc_attr( $tier ),
			esc_attr( $tooltip )
		);

		echo '<span class="qtfw-sold-badge__icon" aria-hidden="true">' . $this->get_icon_svg() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		echo '<span class="qtfw-sold-badge__value">' . esc_html( $formatted ) . '</span>';
		echo '</span>';
	}

	/**
	 * Sales performance tier for badge styling.
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
		return '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 19V5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M4 19H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M8 15V11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 15V8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M16 15V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
	}

	/**
	 * Sort the products list by total_sales when Sold is selected.
	 *
	 * @param \WP_Query $query Query instance.
	 */
	public function custom_sorting_for_sold_column( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		global $pagenow;

		if ( 'edit.php' !== $pagenow ) {
			return;
		}

		if ( 'product' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'product_sold' !== $query->get( 'orderby' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}

		$query->set( 'meta_key', 'total_sales' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
