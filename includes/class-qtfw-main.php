<?php
/**
 * Main plugin bootstrap class.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.0.0
 */

namespace QTFW\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads features and enqueues assets.
 */
class Main {

	/**
	 * Singleton instance.
	 *
	 * @var Main|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Main
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
		$this->initialize_features();
	}

	/**
	 * Register asset hooks.
	 */
	private function define_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue frontend CSS only on WooCommerce product views.
	 */
	public function enqueue_public_assets() {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		if ( ! is_woocommerce() && ! is_product() && ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}

		wp_enqueue_style(
			'qtfw-main',
			QTFW_PLUGIN_URL . 'assets/css/style.css',
			array(),
			QTFW_VERSION
		);
	}

	/**
	 * Enqueue admin CSS only on product and order list screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = ( $screen && isset( $screen->id ) ) ? (string) $screen->id : '';

		$is_products = ( 'edit-product' === $screen_id );
		$is_orders   = (
			'edit-shop_order' === $screen_id
			|| 'woocommerce_page_wc-orders' === $screen_id
			|| false !== strpos( (string) $hook_suffix, 'wc-orders' )
		);

		if ( ! $is_products && ! $is_orders ) {
			return;
		}

		wp_enqueue_style(
			'qtfw-admin',
			QTFW_PLUGIN_URL . 'assets/css/admin-style.css',
			array(),
			QTFW_VERSION
		);
	}

	/**
	 * Load feature class files.
	 */
	private function load_dependencies() {
		require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/total-sold.php';
		require_once QTFW_PLUGIN_DIR . 'includes/classes/total-sold.php';
		require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/columns/PreviousOrdersColumn.php';
	}

	/**
	 * Instantiate feature classes.
	 */
	private function initialize_features() {
		if ( is_admin() ) {
			new \QTFW\Includes\Admin\Classes\Total_Sold();
			new \QTFW\Includes\Admin\Classes\Columns\PreviousOrdersColumn();
		}

		new \QTFW\Includes\Classes\Total_Sold();
	}
}
