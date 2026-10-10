<?php
/**
 * Admin settings page.
 *
 * @package QuickToolsForWooCommerce
 * @since   1.1.0
 */

namespace QTFW\Includes\Admin\Classes;

use QTFW\Includes\Classes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the QuickTools admin panel and settings.
 */
class Admin {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'qtfw-settings';

	/**
	 * Hook suffix for the settings screen.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Register admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( QTFW_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add WooCommerce submenu page.
	 */
	public function register_menu() {
		$this->hook_suffix = add_submenu_page(
			'woocommerce',
			__( 'QuickTools', 'quicktools-for-woocommerce' ),
			__( 'QuickTools', 'quicktools-for-woocommerce' ),
			Settings::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the settings option with the Settings API.
	 */
	public function register_settings() {
		register_setting(
			'qtfw_settings_group',
			Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::instance(), 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Enqueue settings-page assets only on this screen.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'qtfw-admin-settings',
			QTFW_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			QTFW_VERSION
		);

		wp_enqueue_script(
			'qtfw-admin-settings',
			QTFW_PLUGIN_URL . 'assets/js/admin-settings.js',
			array(),
			QTFW_VERSION,
			true
		);
	}

	/**
	 * Add Settings link on the Plugins screen, before Deactivate.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		if ( ! current_user_can( Settings::CAPABILITY ) ) {
			return $links;
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ),
			esc_html__( 'Settings', 'quicktools-for-woocommerce' )
		);

		// Place Settings immediately before the Deactivate action.
		if ( isset( $links['deactivate'] ) ) {
			$reordered = array();

			foreach ( $links as $key => $link ) {
				if ( 'deactivate' === $key ) {
					$reordered['qtfw_settings'] = $settings_link;
				}
				$reordered[ $key ] = $link;
			}

			return $reordered;
		}

		return array_merge( array( 'qtfw_settings' => $settings_link ), $links );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( Settings::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'quicktools-for-woocommerce' ) );
		}

		$settings = Settings::instance()->all();
		$features = Settings::feature_definitions();
		$enabled  = count( array_filter( $settings ) );
		$total    = count( $settings );

		include QTFW_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
	}
}
