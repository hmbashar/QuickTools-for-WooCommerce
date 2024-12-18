<?php
/**
 * Plugin Name: QuickTools for WooCommerce
 * Plugin URI:  https://github.com/hmbashar/QuickTools-for-WooCommerce
 * Description: QuickTools for WooCommerce is a powerful and user-friendly plugin designed to enhance your WooCommerce store with essential tools for better product management. This plugin provides valuable insights into your product sales by adding a "Total Sold" column to the products table, making it easier for you to monitor performance and inventory at a glance.
 * Version:     1.0.0
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
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('QTFW_VERSION', '1.0.0');
define('QTFW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QTFW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Declare compatibility with HPOS and Remote Logging.
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('remote_logging', __FILE__, true);
    }
});

// WooCommerce activation check.
function qtfw_is_woocommerce_active() {
    return true;
}

if (qtfw_is_woocommerce_active()) {

    // Load plugin textdomain.
    function qtfw_load_textdomain() {
        load_plugin_textdomain('quicktools-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('plugins_loaded', 'qtfw_load_textdomain');

    // Include the main class.
    function qtfw_include_main_class() {
        require_once QTFW_PLUGIN_DIR . 'includes/class-qtfw-main.php';
        \QTFW\Includes\Main::get_instance();
    }
    add_action('plugins_loaded', 'qtfw_include_main_class', 20);

} else {

    /**
     * Show a notice when WooCommerce is not active.
     */
    function qtfw_wc_inactive_notice() {
        if (current_user_can('activate_plugins')) {
            echo '<div class="notice notice-error is-dismissible">
                <p><strong>' . esc_html__('QuickTools for WooCommerce', 'quicktools-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and active. Please activate WooCommerce to use this plugin.', 'quicktools-for-woocommerce') . '</p>
            </div>';
        }
    }
    add_action('admin_notices', 'qtfw_wc_inactive_notice');
}

/**
 * Prevent activation if WooCommerce is not active.
 */
function qtfw_plugin_activation_check() {
    if (!qtfw_is_woocommerce_active()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<strong>' . esc_html__('QuickTools for WooCommerce', 'quicktools-for-woocommerce') . '</strong> ' .
            esc_html__('requires WooCommerce to be installed and active. The plugin has been deactivated.', 'quicktools-for-woocommerce') .
            '<br><a href="' . esc_url(admin_url('plugins.php')) . '">' . esc_html__('Go back to the Plugins page.', 'quicktools-for-woocommerce') . '</a>'
        );
    }
}
register_activation_hook(__FILE__, 'qtfw_plugin_activation_check');
