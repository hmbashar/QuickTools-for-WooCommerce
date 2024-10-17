<?php
namespace QTFW\Includes;

class Main {
    
    // Singleton instance
    private static $instance = null;
    
    // Get the singleton instance
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Constructor
    private function __construct() {
        $this->define_hooks();
    }

    // Define hooks and actions
    private function define_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    // Enqueue admin scripts and styles
    public function enqueue_admin_assets() {
        wp_enqueue_style('qtfw-admin', QTFW_PLUGIN_URL . 'assets/css/admin.css', [], QTFW_VERSION);
        wp_enqueue_script('qtfw-admin', QTFW_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], QTFW_VERSION, true);
    }

    // Enqueue public scripts and styles
    public function enqueue_public_assets() {
        wp_enqueue_style('qtfw-public', QTFW_PLUGIN_URL . 'assets/css/public.css', [], QTFW_VERSION);
        wp_enqueue_script('qtfw-public', QTFW_PLUGIN_URL . 'assets/js/public.js', ['jquery'], QTFW_VERSION, true);
    }
}