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
		$phone_core    = $this->get_phone_core( $billing_phone );

		if ( '' === $billing_phone || '' === $phone_core ) {
			echo esc_html__( 'No Phone Number', 'quicktools-for-woocommerce' );
			return;
		}

		$order_counts = $this->get_orders_by_status_counts( $billing_phone, (int) $order->get_id() );
		$this->render_tooltip_circles( $order_counts );
	}

	/**
	 * Sanitize a raw billing phone string.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	private function normalize_phone( $phone ) {
		if ( ! is_string( $phone ) ) {
			return '';
		}

		return trim( sanitize_text_field( wp_unslash( $phone ) ) );
	}

	/**
	 * Digits-only form of a phone number.
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function phone_digits( $phone ) {
		return preg_replace( '/\D+/', '', (string) $phone );
	}

	/**
	 * Store calling-code digits from WooCommerce base country (e.g. 880).
	 *
	 * @return string
	 */
	private function get_store_calling_code_digits() {
		if ( ! function_exists( 'WC' ) || ! WC()->countries ) {
			return '';
		}

		$base    = function_exists( 'wc_get_base_location' ) ? wc_get_base_location() : array();
		$country = isset( $base['country'] ) ? (string) $base['country'] : '';

		if ( '' === $country ) {
			return '';
		}

		$code = WC()->countries->get_country_calling_code( $country );

		return $this->phone_digits( is_string( $code ) ? $code : '' );
	}

	/**
	 * National phone core shared by local and country-code formats.
	 *
	 * Examples (BD / 880): +8801712345678, 8801712345678, 01712345678 → 1712345678
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function get_phone_core( $phone ) {
		$digits = $this->phone_digits( $phone );

		if ( '' === $digits ) {
			return '';
		}

		// Strip international "00" prefix.
		if ( 0 === strpos( $digits, '00' ) ) {
			$digits = substr( $digits, 2 );
		}

		$calling = $this->get_store_calling_code_digits();

		// Strip store country calling code when present.
		if (
			'' !== $calling
			&& 0 === strpos( $digits, $calling )
			&& strlen( $digits ) > ( strlen( $calling ) + 6 )
		) {
			$digits = substr( $digits, strlen( $calling ) );
		}

		// Strip trunk prefix (leading zeros).
		$digits = ltrim( $digits, '0' );

		return $digits;
	}

	/**
	 * SQL expression that strips common phone formatting characters.
	 *
	 * Column must be a hard-coded safe identifier (never user input).
	 *
	 * @param string $column Column expression, e.g. pm.meta_value or a.phone.
	 * @return string
	 */
	private function sql_phone_digits_expr( $column ) {
		$allowed = array( 'pm.meta_value', 'a.phone' );

		if ( ! in_array( $column, $allowed, true ) ) {
			return "'0'";
		}

		$expr = $column;

		foreach ( array( ' ', '-', '+', '(', ')', '.', '/', "\t" ) as $char ) {
			$expr = 'REPLACE(' . $expr . ", '" . $char . "', '')";
		}

		return $expr;
	}

	/**
	 * Build SQL + prepare args that match the same number with/without country code.
	 *
	 * @param string $column Safe phone column expression.
	 * @param string $phone  Raw/sanitized phone from the current order.
	 * @return array{sql:string,values:array<int,int|string>}|null
	 */
	private function get_phone_match_clause( $column, $phone ) {
		$core = $this->get_phone_core( $phone );

		if ( '' === $core ) {
			return null;
		}

		$digits_sql = $this->sql_phone_digits_expr( $column );
		$core_len   = strlen( $core );

		// Short numbers: exact digit match only (avoid over-matching).
		if ( $core_len < 7 ) {
			return array(
				'sql'    => "{$digits_sql} = %s",
				'values' => array( $this->phone_digits( $phone ) ),
			);
		}

		/*
		 * Match when digit-only phone ends with the national core.
		 * Covers: 017..., +88017..., 88017..., spaces/dashes, etc.
		 */
		return array(
			'sql'    => "(CHAR_LENGTH({$digits_sql}) >= %d AND RIGHT({$digits_sql}, %d) = %s)",
			'values' => array( $core_len, $core_len, $core ),
		);
	}

	/**
	 * Get order counts by status for a phone, excluding the current order.
	 *
	 * @param string $phone_number     Sanitized billing phone.
	 * @param int    $exclude_order_id Current order ID to exclude.
	 * @return array<string, int>
	 */
	private function get_orders_by_status_counts( $phone_number, $exclude_order_id = 0 ) {
		$phone_core = $this->get_phone_core( $phone_number );
		$cache_key  = md5( $phone_core . '|' . (int) $exclude_order_id );

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

		$match = $this->get_phone_match_clause( 'pm.meta_value', $phone_number );

		if ( null === $match ) {
			return $this->empty_status_counts();
		}

		$sql = "SELECT p.post_status AS status, COUNT(*) AS count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
			WHERE pm.meta_key = '_billing_phone'
			AND {$match['sql']}
			AND p.post_type = 'shop_order'
			AND p.ID != %d
			GROUP BY p.post_status";

		$args   = $match['values'];
		$args[] = (int) $exclude_order_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe column expr; values prepared below.
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

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

		$match = $this->get_phone_match_clause( 'a.phone', $phone_number );

		if ( null === $match ) {
			return $this->empty_status_counts();
		}

		$orders_table    = $wpdb->prefix . 'wc_orders';
		$addresses_table = $wpdb->prefix . 'wc_order_addresses';

		$sql = "SELECT o.status AS status, COUNT(*) AS count
			FROM {$orders_table} o
			INNER JOIN {$addresses_table} a ON a.order_id = o.id
			WHERE a.address_type = 'billing'
			AND {$match['sql']}
			AND o.type = 'shop_order'
			AND o.id != %d
			GROUP BY o.status";

		$args   = $match['values'];
		$args[] = (int) $exclude_order_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Safe column expr; values prepared below.
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) );

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
			array(
				'label' => __( 'Total previous orders', 'quicktools-for-woocommerce' ),
				'count' => (int) ( $counts['total'] ?? 0 ),
				'color' => '#6c757d',
			),
			array(
				'label' => __( 'Processing', 'quicktools-for-woocommerce' ),
				'count' => (int) ( $counts['processing'] ?? 0 ),
				'color' => '#28a745',
			),
			array(
				'label' => __( 'On hold', 'quicktools-for-woocommerce' ),
				'count' => (int) ( $counts['on-hold'] ?? 0 ),
				'color' => '#17a2b8',
			),
			array(
				'label' => __( 'Pending payment', 'quicktools-for-woocommerce' ),
				'count' => (int) ( $counts['pending'] ?? 0 ),
				'color' => '#ffc107',
			),
			array(
				'label' => __( 'Cancelled', 'quicktools-for-woocommerce' ),
				'count' => (int) ( $counts['cancelled'] ?? 0 ),
				'color' => '#dc3545',
			),
		);

		echo '<div class="qtfw-previous-orders">';

		foreach ( $statuses as $status ) {
			$tooltip = sprintf(
				/* translators: 1: order status label, 2: count */
				__( '%1$s: %2$d', 'quicktools-for-woocommerce' ),
				$status['label'],
				$status['count']
			);

			printf(
				'<span class="qtfw-tooltip-circle" style="background-color:%1$s;" data-tooltip="%2$s" title="%2$s" aria-label="%2$s" tabindex="0">%3$s</span>',
				esc_attr( $status['color'] ),
				esc_attr( $tooltip ),
				esc_html( (string) $status['count'] )
			);
		}

		echo '</div>';
	}
}
