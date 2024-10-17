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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }


    // Enqueue public scripts and styles
    public function enqueue_public_assets() {
        wp_enqueue_style('qtfw-main', QTFW_PLUGIN_URL . 'assets/css/style.css', [], QTFW_VERSION);       
    }
}