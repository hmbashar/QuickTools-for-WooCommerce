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

		echo esc_html( (string) (int) $product->get_total_sales() );
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
