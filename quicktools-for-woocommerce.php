<?php
/**
 * Plugin Name: QuickTools for WooCommerce
 * Plugin URI:  https://github.com/hmbashar/QuickTools-for-WooCommerce
 * Description: Premium WooCommerce toolkit with Total Sold insights and Previous Orders badges — each feature can be enabled or disabled from the Control Center.
 * Version:     1.3.0
 * Author:      Md Abul Bashar
 * Author URI:  https://github.com/hmbashar
 * Text Domain: quicktools-for-woocommerce
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 6.0
 * WC tested up to: 9.5.1
 * Requires Plugins: woocommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'QTFW_VERSION', '1.3.0' );
define( 'QTFW_PLUGIN_FILE', __FILE__ );
define( 'QTFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QTFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Seed default settings on activation.
 */
function qtfw_activate() {
	require_once QTFW_PLUGIN_DIR . 'includes/classes/class-qtfw-settings.php';
	\QTFW\Includes\Classes\Settings::maybe_install();
}
register_activation_hook( __FILE__, 'qtfw_activate' );

// Check if WooCommerce is active.
$qtfw_wc_active = in_array( 'woocommerce/woocommerce.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true );

if ( ! $qtfw_wc_active && function_exists( 'is_multisite' ) && is_multisite() ) {
	$qtfw_wc_active = array_key_exists( 'woocommerce/woocommerce.php', (array) get_site_option( 'active_sitewide_plugins', array() ) );
}

if ( $qtfw_wc_active ) {

	// Declare compatibility with HPOS and Remote Logging.
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', QTFW_PLUGIN_FILE, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'remote_logging', QTFW_PLUGIN_FILE, true );
			}
		}
	);

	/**
	 * Load plugin textdomain.
	 */
	function qtfw_load_textdomain() {
		load_plugin_textdomain( 'quicktools-for-woocommerce', false, dirname( plugin_basename( QTFW_PLUGIN_FILE ) ) . '/languages' );
	}
	add_action( 'init', 'qtfw_load_textdomain' );

	require_once QTFW_PLUGIN_DIR . 'includes/class-qtfw-main.php';

	/**
	 * Initialize the plugin after plugins are loaded.
	 */
	function qtfw_initialize_plugin() {
		\QTFW\Includes\Main::get_instance();
	}
	add_action( 'plugins_loaded', 'qtfw_initialize_plugin' );

} else {

	/**
	 * Show a notice when WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	function qtfw_wc_inactive_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'QuickTools for WooCommerce', 'quicktools-for-woocommerce' ) . '</strong> ' . esc_html__( 'requires WooCommerce to be installed and active. Please activate WooCommerce to use this plugin.', 'quicktools-for-woocommerce' ) . '</p></div>';
	}

	add_action( 'admin_notices', 'qtfw_wc_inactive_notice' );
}
