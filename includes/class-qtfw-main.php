<?php
/**
 * Main plugin bootstrap class.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.0.0
 */

namespace QTFW\Includes;

use QTFW\Includes\Classes\Settings;

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
	 * Settings helper.
	 *
	 * @var Settings
	 */
	private $settings;

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
		require_once QTFW_PLUGIN_DIR . 'includes/classes/class-qtfw-settings.php';

		Settings::maybe_install();
		$this->settings = Settings::instance();

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
	 * Enqueue frontend CSS only on relevant product views when enabled.
	 */
	public function enqueue_public_assets() {
		if ( ! $this->is_any_storefront_feature_enabled() ) {
			return;
		}

		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		$show = is_product()
			|| is_shop()
			|| is_product_taxonomy()
			|| ( is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() );

		if ( ! $show ) {
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
	 * Whether any storefront feature that uses style.css is enabled.
	 *
	 * @return bool
	 */
	private function is_any_storefront_feature_enabled() {
		foreach ( array( 'frontend_total_sold_loop', 'frontend_total_sold_single', 'recently_sold', 'only_left', 'best_seller', 'sale_countdown' ) as $key ) {
			if ( $this->settings->is_enabled( $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue admin list CSS only for enabled feature screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = ( $screen && isset( $screen->id ) ) ? (string) $screen->id : '';

		$is_products = ( 'edit-product' === $screen_id )
			&& ( $this->settings->is_enabled( 'admin_total_sold' ) || $this->settings->is_enabled( 'stock_qty' ) );

		$is_orders = $this->settings->is_enabled( 'previous_orders' ) && (
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
	 * Load class files required for enabled features.
	 */
	private function load_dependencies() {
		if ( is_admin() ) {
			require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/class-qtfw-admin.php';

			if ( $this->settings->is_enabled( 'admin_total_sold' ) ) {
				require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/total-sold.php';
			}

			if ( $this->settings->is_enabled( 'stock_qty' ) ) {
				require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/stock-qty.php';
			}

			if ( $this->settings->is_enabled( 'previous_orders' ) ) {
				require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/columns/PreviousOrdersColumn.php';
			}
		}

		if ( $this->settings->is_enabled( 'low_stock_email' ) ) {
			require_once QTFW_PLUGIN_DIR . 'includes/classes/low-stock-email.php';
		}

		if ( $this->settings->is_frontend_total_sold_enabled() ) {
			require_once QTFW_PLUGIN_DIR . 'includes/classes/total-sold.php';
		}

		if ( $this->settings->is_enabled( 'recently_sold' ) ) {
			require_once QTFW_PLUGIN_DIR . 'includes/classes/recently-sold.php';
		}

		if ( $this->settings->is_enabled( 'only_left' ) ) {
			require_once QTFW_PLUGIN_DIR . 'includes/classes/only-left.php';
		}
	}

	/**
	 * Instantiate enabled features only.
	 */
	private function initialize_features() {
		if ( is_admin() ) {
			new \QTFW\Includes\Admin\Classes\Admin();

			if ( $this->settings->is_enabled( 'admin_total_sold' ) ) {
				new \QTFW\Includes\Admin\Classes\Total_Sold();
			}

			if ( $this->settings->is_enabled( 'stock_qty' ) ) {
				new \QTFW\Includes\Admin\Classes\Stock_Qty();
			}

			if ( $this->settings->is_enabled( 'previous_orders' ) ) {
				new \QTFW\Includes\Admin\Classes\Columns\PreviousOrdersColumn();
			}
		}

		if ( $this->settings->is_enabled( 'low_stock_email' ) ) {
			new \QTFW\Includes\Classes\Low_Stock_Email();
		}

		if ( $this->settings->is_frontend_total_sold_enabled() ) {
			new \QTFW\Includes\Classes\Total_Sold(
				$this->settings->is_enabled( 'frontend_total_sold_loop' ),
				$this->settings->is_enabled( 'frontend_total_sold_single' )
			);
		}

		if ( $this->settings->is_enabled( 'recently_sold' ) ) {
			new \QTFW\Includes\Classes\Recently_Sold();
		}

		if ( $this->settings->is_enabled( 'only_left' ) ) {
			new \QTFW\Includes\Classes\Only_Left();
		}
	}
}
