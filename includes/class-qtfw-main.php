<?php
namespace QTFW\Includes;

/**
 * Don't call the file directly
 *
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}



class Main
{

    /**
     * The singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @var Main The singleton instance.
     */
    private static $instance = null;

    
    /**
     * Gets the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return Main The singleton instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

   
    /**
     * Constructor for the Main class.
     *
     * This private constructor initializes the plugin by loading
     * required dependencies, defining necessary hooks, and initializing
     * admin features. It ensures that these operations are performed
     * when the singleton instance of the class is created.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->load_dependencies(); // Load all required files
        $this->define_hooks();
        $this->initialize_admin_features();

    }

    /**
     * Defines the hooks for the plugin.
     */
    private function define_hooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }


    /**
     * Enqueues public assets for the plugin.
     *
     * @since 1.0.0
     */
    public function enqueue_public_assets()
    {
        wp_enqueue_style('qtfw-main', QTFW_PLUGIN_URL . 'assets/css/style.css', [], QTFW_VERSION);
    }

    /**
     * Enqueues admin assets for the plugin.
     *
     * @since 1.0.0
     */
    public function enqueue_admin_assets()
    {
        wp_enqueue_style('qtfw-admin', QTFW_PLUGIN_URL . 'assets/css/admin-style.css', [], QTFW_VERSION);
    }
   
    /**
     * Loads all required files for the plugin to work correctly.
     *
     * @since 1.0.0
     */
    private function load_dependencies() {
        // Require the file that contains the Total_Sold class
        require_once QTFW_PLUGIN_DIR . 'includes/admin/classes/total-sold.php';

        // Require the file that contains the Total_Sold class
        require_once QTFW_PLUGIN_DIR . 'includes/classes/total-sold.php';
    }

    /**
     * Initializes features that are only available in the WordPress admin.
     * Currently this only includes the Total_Sold feature.
     */
    private function initialize_admin_features() {
        if (is_admin()) {
            // Initialize Total_Sold feature for admin
            new \QTFW\Includes\Admin\Classes\Total_Sold();
        }

        // Initialize Total_Sold feature for public
        if (class_exists('\QTFW\Includes\Classes\Total_Sold')) {
            // Initialize Total_Sold feature for public
            new \QTFW\Includes\Classes\Total_Sold();
        }
    }
    

}