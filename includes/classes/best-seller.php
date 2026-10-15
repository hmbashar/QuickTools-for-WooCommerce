<?php
/**
 * Frontend "Best Seller" badge.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.6.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marks the top-selling products with a Best Seller badge.
 */
class Best_Seller {

	/**
	 * Transient holding the list of top product IDs.
	 *
	 * @var string
	 */
	const TRANSIENT = 'qtfw_best_seller_ids';

	/**
	 * Cache lifetime in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Default number of products marked as best sellers.
	 *
	 * @var int
	 */
	const DEFAULT_COUNT = 5;

	/**
	 * Request-level copy of the ID list.
	 *
	 * @var int[]|null
	 */
	private static $ids = null;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'render' ), 14 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 4 );
	}

	/**
	 * Output the badge for the current product.
	 */
	public function render() {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! in_array( $product->get_id(), self::get_top_ids(), true ) ) {
			return;
		}

		echo '<div class="qtfw-best-seller" role="status"><span aria-hidden="true">&#9733;</span> ' . esc_html__( 'Best Seller', 'quicktools-for-woocommerce' ) . '</div>';
	}

	/**
	 * IDs of the top-selling products (by lifetime units sold).
	 *
	 * @return int[]
	 */
	private static function get_top_ids() {
		if ( null !== self::$ids ) {
			return self::$ids;
		}

		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) ) {
			self::$ids = $cached;
			return self::$ids;
		}

		$count = max( 1, (int) apply_filters( 'qtfw_best_seller_count', self::DEFAULT_COUNT ) );

		$ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => $count,
				'fields'                 => 'ids',
				'meta_key'               => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- cached for an hour.
				'orderby'                => 'meta_value_num',
				'order'                  => 'DESC',
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- cached for an hour.
					array(
						'key'     => 'total_sales',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		self::$ids = array_map( 'intval', $ids );
		set_transient( self::TRANSIENT, self::$ids, self::CACHE_TTL );

		return self::$ids;
	}
}
