<?php
/**
 * Plugin Name: QuickTools for WooCommerce
 * Plugin URI:  https://github.com/hmbashar/QuickTools-for-WooCommerce
 * Description: A set of quick tools for WooCommerce stores to enhance functionality.
 * Version:     1.0.0
 * Author:      Md Abul Bashar
 * Author URI:  https://github.com/hmbashar
 * Text Domain: qtfw
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
        load_plugin_textdomain('qtfw', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
    // Display an admin notice if WooCommerce is not active.
    function qtfw_wc_inactive_notice() {
        echo '<div class="notice notice-error is-dismissible">
            <p><strong>' . esc_html__('QuickTools for WooCommerce', 'qtfw') . '</strong> ' . esc_html__('requires WooCommerce to be installed and active. Please activate WooCommerce to use this plugin.', 'qtfw') . '</p>
        </div>';
    }
    
    add_action('admin_notices', 'qtfw_wc_inactive_notice');
}

