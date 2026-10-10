<?php
/**
 * Previous Orders column for the WooCommerce orders list.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.0.0
 */

namespace QTFW\Includes\Admin\Classes\Columns;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a Previous Orders column keyed by billing phone.
 */
class PreviousOrdersColumn {

	/**
	 * Request-level cache of phone => status counts.
	 *
	 * @var array<string, array<string, int>>
	 */
	private static $counts_cache = array();

	/**
	 * Status keys shown in the column UI.
	 *
	 * @var string[]
	 */
	private static $tracked_statuses = array(
		'processing',
		'on-hold',
		'pending',
		'cancelled',
	);

	/**
	 * Register list-table hooks for legacy CPT and HPOS screens.
	 */
	public function __construct() {
		// Legacy shop_order CPT list.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_previous_orders_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_previous_orders_column_legacy' ), 10, 2 );

		// HPOS orders list (WooCommerce 7.3+).
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_previous_orders_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_previous_orders_column_hpos' ), 10, 2 );
	}

	/**
	 * Add the Previous Orders column after Order Total.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_previous_orders_column( $columns ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return $columns;
		}

		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;

			if ( 'order_total' === $key ) {
				$new_columns['previous_orders'] = __( 'Previous Orders', 'quicktools-for-woocommerce' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render column for the legacy orders list (post ID).
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Order post ID.
	 */
	public function render_previous_orders_column_legacy( $column, $post_id ) {
		if ( 'previous_orders' !== $column ) {
			return;
		}

		$this->render_previous_orders_for_order( wc_get_order( $post_id ) );
	}

	/**
	 * Render column for the HPOS orders list (order object or ID).
	 *
	 * @param string         $column Column key.
	 * @param \WC_Order|int  $order  Order object or ID.
	 */
	public function render_previous_orders_column_hpos( $column, $order ) {
		if ( 'previous_orders' !== $column ) {
			return;
		}

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order );
		}

		$this->render_previous_orders_for_order( $order );
	}

	/**
	 * Output previous-order status badges for a single order row.
	 *
	 * @param \WC_Order|false|null $order Order instance.
	 */
	private function render_previous_orders_for_order( $order ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		if ( ! $order instanceof \WC_Order ) {
			echo '&mdash;';
			return;
		}

		$billing_phone = $this->normalize_phone( $order->get_billing_phone() );

		if ( '' === $billing_phone ) {
			echo esc_html__( 'No Phone Number', 'quicktools-for-woocommerce' );
			return;
		}

		$order_counts = $this->get_orders_by_status_counts( $billing_phone, (int) $order->get_id() );
		$this->render_tooltip_circles( $order_counts );
	}

	/**
	 * Normalize a billing phone for lookup/caching.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	private function normalize_phone( $phone ) {
		if ( ! is_string( $phone ) ) {
			return '';
		}

		// Keep formatting as stored so SQL exact-match still works.
		return trim( sanitize_text_field( wp_unslash( $phone ) ) );
	}

	/**
	 * Get order counts by status for a phone, excluding the current order.
	 *
	 * @param string $phone_number     Normalized billing phone.
	 * @param int    $exclude_order_id Current order ID to exclude.
	 * @return array<string, int>
	 */
	private function get_orders_by_status_counts( $phone_number, $exclude_order_id = 0 ) {
		$cache_key = md5( $phone_number . '|' . (int) $exclude_order_id );

		if ( isset( self::$counts_cache[ $cache_key ] ) ) {
			return self::$counts_cache[ $cache_key ];
		}

		$empty = $this->empty_status_counts();

		try {
			if ( $this->is_hpos_enabled() ) {
				$counts = $this->get_orders_by_status_counts_hpos( $phone_number, $exclude_order_id );
			} else {
				$counts = $this->get_orders_by_status_counts_legacy( $phone_number, $exclude_order_id );
			}
		} catch ( \Exception $e ) {
			$counts = $empty;
		}

		self::$counts_cache[ $cache_key ] = $counts;

		return $counts;
	}

	/**
	 * Default zeroed status counts.
	 *
	 * @return array<string, int>
	 */
	private function empty_status_counts() {
		$counts = array( 'total' => 0 );

		foreach ( self::$tracked_statuses as $status ) {
			$counts[ $status ] = 0;
		}

		return $counts;
	}

	/**
	 * Map SQL status rows into the UI count array.
	 *
	 * @param array<int, object> $results DB rows with status + count.
	 * @return array<string, int>
	 */
	private function map_status_results( $results ) {
		$statuses = $this->empty_status_counts();

		foreach ( (array) $results as $row ) {
			if ( ! isset( $row->status, $row->count ) ) {
				continue;
			}

			$count  = (int) $row->count;
			$status = (string) $row->status;

			if ( 0 === strpos( $status, 'wc-' ) ) {
				$status = substr( $status, 3 );
			}

			$statuses['total'] += $count;

			if ( isset( $statuses[ $status ] ) && 'total' !== $status ) {
				$statuses[ $status ] += $count;
			}
		}

		return $statuses;
	}

	/**
	 * Count orders by status (legacy CPT storage).
	 *
	 * @param string $phone_number     Billing phone.
	 * @param int    $exclude_order_id Order ID to exclude.
	 * @return array<string, int>
	 */
	private function get_orders_by_status_counts_legacy( $phone_number, $exclude_order_id = 0 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list aggregate; request-cached above.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_status AS status, COUNT(*) AS count
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				 WHERE pm.meta_key = '_billing_phone'
				 AND pm.meta_value = %s
				 AND p.post_type = 'shop_order'
				 AND p.ID != %d
				 GROUP BY p.post_status",
				$phone_number,
				(int) $exclude_order_id
			)
		);

		return $this->map_status_results( $results );
	}

	/**
	 * Count orders by status (HPOS / custom order tables).
	 *
	 * Reads billing phone from wc_order_addresses, not postmeta.
	 *
	 * @param string $phone_number     Billing phone.
	 * @param int    $exclude_order_id Order ID to exclude.
	 * @return array<string, int>
	 */
	private function get_orders_by_status_counts_hpos( $phone_number, $exclude_order_id = 0 ) {
		global $wpdb;

		$orders_table    = $wpdb->prefix . 'wc_orders';
		$addresses_table = $wpdb->prefix . 'wc_order_addresses';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin list aggregate; request-cached above.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.status AS status, COUNT(*) AS count
				 FROM {$orders_table} o
				 INNER JOIN {$addresses_table} a ON a.order_id = o.id
				 WHERE a.address_type = 'billing'
				 AND a.phone = %s
				 AND o.type = 'shop_order'
				 AND o.id != %d
				 GROUP BY o.status",
				$phone_number,
				(int) $exclude_order_id
			)
		);

		return $this->map_status_results( $results );
	}

	/**
	 * Whether custom order tables (HPOS) are in use.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& method_exists( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' )
		) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		return false;
	}

	/**
	 * Render status count badges with accessible tooltips.
	 *
	 * @param array<string, int> $counts Status counts.
	 */
	private function render_tooltip_circles( $counts ) {
		$statuses = array(
			'Total'      => array(
				'count' => (int) ( $counts['total'] ?? 0 ),
				'color' => '#aaa',
			),
			'Processing' => array(
				'count' => (int) ( $counts['processing'] ?? 0 ),
				'color' => '#28a745',
			),
			'On Hold'    => array(
				'count' => (int) ( $counts['on-hold'] ?? 0 ),
				'color' => '#17a2b8',
			),
			'Pending'    => array(
				'count' => (int) ( $counts['pending'] ?? 0 ),
				'color' => '#ffc107',
			),
			'Cancelled'  => array(
				'count' => (int) ( $counts['cancelled'] ?? 0 ),
				'color' => '#dc3545',
			),
		);

		echo '<div class="qtfw-previous-orders">';

		foreach ( $statuses as $label => $status ) {
			$tooltip = sprintf(
				/* translators: 1: order status label, 2: count */
				__( '%1$s: %2$d', 'quicktools-for-woocommerce' ),
				$label,
				$status['count']
			);

			printf(
				'<span class="qtfw-tooltip-circle" style="background-color:%1$s;" title="%2$s" aria-label="%2$s">%3$s</span>',
				esc_attr( $status['color'] ),
				esc_attr( $tooltip ),
				esc_html( (string) $status['count'] )
			);
		}

		echo '</div>';
	}
}
