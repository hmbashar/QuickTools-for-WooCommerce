<?php
/**
 * Frontend "Recently sold" label.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.3.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows how many units of a product sold in the last 24 hours.
 */
class Recently_Sold {

	/**
	 * Transient holding the product_id => units map.
	 *
	 * @var string
	 */
	const TRANSIENT = 'qtfw_recently_sold_24h';

	/**
	 * Cache lifetime in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL = 600;

	/**
	 * Maximum orders scanned per refresh.
	 *
	 * @var int
	 */
	const ORDER_LIMIT = 1000;

	/**
	 * Request-level copy of the map.
	 *
	 * @var array<int, int>|null
	 */
	private static $map = null;

	/**
	 * Register hooks.
	 *
	 * @param bool $show_loop   Enable loop display.
	 * @param bool $show_single Enable single product display.
	 */
	public function __construct( $show_loop = true, $show_single = true ) {
		if ( $show_loop ) {
			add_action( 'woocommerce_after_shop_loop_item_title', array( $this, 'render' ), 16 );
		}

		if ( $show_single ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 26 );
		}

		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * Clear the cached map.
	 */
	public static function flush_cache() {
		delete_transient( self::TRANSIENT );
		self::$map = null;
	}

	/**
	 * Output the label for the current product.
	 */
	public function render() {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$map   = self::get_map();
		$units = isset( $map[ $product->get_id() ] ) ? (int) $map[ $product->get_id() ] : 0;

		if ( $units < 1 ) {
			return;
		}

		$text = sprintf(
			/* translators: %s: formatted number of units sold in the last 24 hours */
			_n( '%s sold in the last 24 hours', '%s sold in the last 24 hours', $units, 'quicktools-for-woocommerce' ),
			number_format_i18n( $units )
		);

		echo '<div class="qtfw-recently-sold" role="status"><span class="qtfw-recently-sold__dot" aria-hidden="true"></span>' . esc_html( $text ) . '</div>';
	}

	/**
	 * Get units sold per product (variations roll up to the parent) in the last 24 hours.
	 *
	 * @return array<int, int>
	 */
	private static function get_map() {
		if ( null !== self::$map ) {
			return self::$map;
		}

		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) ) {
			self::$map = $cached;
			return self::$map;
		}

		$map    = array();
		$orders = wc_get_orders(
			array(
				'status'       => wc_get_is_paid_statuses(),
				'date_created' => '>' . ( time() - DAY_IN_SECONDS ),
				'limit'        => self::ORDER_LIMIT,
				'orderby'      => 'date',
				'order'        => 'DESC',
				'return'       => 'objects',
			)
		);

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$id = (int) $item->get_product_id();

				if ( $id > 0 ) {
					$map[ $id ] = ( isset( $map[ $id ] ) ? $map[ $id ] : 0 ) + max( 0, (int) $item->get_quantity() );
				}
			}
		}

		set_transient( self::TRANSIENT, $map, self::CACHE_TTL );
		self::$map = $map;

		return $map;
	}
}
