<?php
/**
 * Plugin settings helper.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.1.0
 */

namespace QTFW\Includes\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and retrieves feature toggle settings.
 */
class Settings {

	/**
	 * Option name in wp_options.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'qtfw_settings';

	/**
	 * Capability required to manage settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Singleton.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Request-level settings cache.
	 *
	 * @var array<string, int>|null
	 */
	private $settings = null;

	/**
	 * Get singleton.
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default settings (all features on for backward compatibility).
	 *
	 * @return array<string, int>
	 */
	public static function defaults() {
		return array(
			'admin_total_sold'           => 1,
			'frontend_total_sold_loop'   => 1,
			'frontend_total_sold_single' => 1,
			'previous_orders'            => 1,
			'stock_qty'                  => 1,
			'recently_sold'              => 1,
			'only_left'                  => 1,
			'low_stock_email'            => 1,
			'best_seller'                => 1,
			'sale_countdown'             => 1,
			'quick_copy'                 => 1,
		);
	}

	/**
	 * Feature metadata for the admin UI.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function feature_definitions() {
		return array(
			'admin_total_sold'           => array(
				'title'       => __( 'Sold column (Products)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show a sortable Sold badge column on the WooCommerce products list.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Admin', 'quicktools-for-woocommerce' ),
			),
			'frontend_total_sold_loop'   => array(
				'title'       => __( 'Total Sold (Shop / Archive)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Display the Total Sold chip on shop, category, and other product loops.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'frontend_total_sold_single' => array(
				'title'       => __( 'Total Sold (Single Product)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Display the Total Sold metric card on single product pages.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'previous_orders'            => array(
				'title'       => __( 'Previous Orders column', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show previous-order status badges on the orders list, matched by billing phone.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Admin', 'quicktools-for-woocommerce' ),
			),
			'stock_qty'                => array(
				'title'       => __( 'Stock Qty column (Products)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show a sortable, color-coded stock quantity (low and out-of-stock highlighted) on the products list.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Admin', 'quicktools-for-woocommerce' ),
			),
			'recently_sold'              => array(
				'title'       => __( 'Recently Sold label (Storefront)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show "12 sold in the last 24 hours" on shop loops and single product pages. Hidden when nothing sold recently.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'only_left'                  => array(
				'title'       => __( 'Only X Left badge (Storefront)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show an "Only 3 left in stock" urgency badge on shop loops and single product pages when stock runs low.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'low_stock_email'            => array(
				'title'       => __( 'Low Stock email alert', 'quicktools-for-woocommerce' ),
				'description' => __( 'Email the store admin once when a product stock falls to its low-stock threshold or below.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Admin', 'quicktools-for-woocommerce' ),
			),
			'best_seller'                => array(
				'title'       => __( 'Best Seller badge (Storefront)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Mark the top-selling products with a Best Seller badge on shop loops and single product pages.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'sale_countdown'             => array(
				'title'       => __( 'Sale Countdown (Storefront)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Show a live countdown on products whose scheduled sale ends within 7 days.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Storefront', 'quicktools-for-woocommerce' ),
			),
			'quick_copy'                 => array(
				'title'       => __( 'Quick ID / SKU copy (Products)', 'quicktools-for-woocommerce' ),
				'description' => __( 'Add one-click "Copy ID" and "Copy SKU" actions to each row on the products list.', 'quicktools-for-woocommerce' ),
				'area'        => __( 'Admin', 'quicktools-for-woocommerce' ),
			),
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, int>
	 */
	public function all() {
		if ( null !== $this->settings ) {
			return $this->settings;
		}

		$stored = get_option( self::OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();

		$merged = self::defaults();

		foreach ( $merged as $key => $default ) {
			if ( array_key_exists( $key, $stored ) ) {
				$merged[ $key ] = ! empty( $stored[ $key ] ) ? 1 : 0;
			}
		}

		$this->settings = $merged;

		return $this->settings;
	}

	/**
	 * Whether a feature is enabled.
	 *
	 * @param string $feature Feature key.
	 * @return bool
	 */
	public function is_enabled( $feature ) {
		$all = $this->all();

		return ! empty( $all[ $feature ] );
	}

	/**
	 * Whether any frontend Total Sold surface is enabled.
	 *
	 * @return bool
	 */
	public function is_frontend_total_sold_enabled() {
		return $this->is_enabled( 'frontend_total_sold_loop' )
			|| $this->is_enabled( 'frontend_total_sold_single' );
	}

	/**
	 * Sanitize settings from the options form.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, int>
	 */
	public function sanitize( $input ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $this->all();
		}

		$input  = is_array( $input ) ? $input : array();
		$output = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
		}

		// Refresh request cache after save.
		$this->settings = $output;

		return $output;
	}

	/**
	 * Ensure the option exists (activation / first load).
	 */
	public static function maybe_install() {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, self::defaults(), '', 'yes' );
		}
	}
}
