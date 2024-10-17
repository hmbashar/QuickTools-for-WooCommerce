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
 * WC requires at least: 4.0
 * WC tested up to: 9.3.3
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

// Check if WooCommerce is active.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || (function_exists('is_multisite') && is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins')))) {

    // Load plugin textdomain.
    function qtfw_load_textdomain() {
        load_plugin_textdomain('quicktools-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    add_action('init', 'qtfw_load_textdomain');

    // Include the main class.
    require_once QTFW_PLUGIN_DIR . 'includes/class-qtfw-main.php';

    // Initialize the plugin.
    function qtfw_initialize_plugin() {
        \QTFW\Includes\Main::get_instance();
    }
    add_action('plugins_loaded', 'qtfw_initialize_plugin');

} else {
   
    /**
     * Show a notice when WooCommerce is not active.
     *
     * @since 1.0.0
     */
    function qtfw_wc_inactive_notice() {
        echo '<div class="notice notice-error is-dismissible">
            <p><strong>' . esc_html__('QuickTools for WooCommerce', 'quicktools-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and active. Please activate WooCommerce to use this plugin.', 'quicktools-for-woocommerce') . '</p>
        </div>';
    }
    
    add_action('admin_notices', 'qtfw_wc_inactive_notice');
}