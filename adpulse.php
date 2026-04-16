<?php
/**
 * Plugin Name: AdPulse sGTM
 * Plugin URI: https://adpulse.com.br
 * Description: Integrate WordPress sites with AdPulse server-side Google Tag Manager using first-party tracking
 * Version: 1.0.0
 * Author: AdPulse
 * Author URI: https://adpulse.com.br
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: adpulse
 * Domain Path: /languages
 *
 * @package AdPulse_sGTM
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ADPULSE_VERSION', '1.0.0' );
define( 'ADPULSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADPULSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADPULSE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load required classes.
require_once ADPULSE_PLUGIN_DIR . 'includes/class-adpulse-admin-menu.php';
require_once ADPULSE_PLUGIN_DIR . 'includes/class-adpulse-settings.php';
require_once ADPULSE_PLUGIN_DIR . 'includes/class-adpulse-settings-page.php';
require_once ADPULSE_PLUGIN_DIR . 'includes/class-adpulse-proxy.php';
require_once ADPULSE_PLUGIN_DIR . 'includes/class-adpulse-gtm-manager.php';

/**
 * Main AdPulse Plugin Class
 */
class AdPulse {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'adpulse', false, dirname( ADPULSE_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Initialize plugin components
	 */
	public function init() {
		// Initialize admin menu.
		AdPulse_Admin_Menu::get_instance();

		// Initialize settings.
		AdPulse_Settings::get_instance();

		// Initialize settings page.
		AdPulse_Settings_Page::get_instance();

		// Initialize proxy.
		AdPulse_Proxy::get_instance();

		// Initialize GTM manager.
		AdPulse_GTM_Manager::get_instance();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'settings_page_adpulse-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'adpulse-admin-styles',
			ADPULSE_PLUGIN_URL . 'assets/css/admin-styles.css',
			array(),
			ADPULSE_VERSION
		);

		wp_enqueue_script(
			'adpulse-admin-scripts',
			ADPULSE_PLUGIN_URL . 'assets/js/admin-scripts.js',
			array(),
			ADPULSE_VERSION,
			true
		);

		wp_localize_script(
			'adpulse-admin-scripts',
			'adpulseSettings',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'adpulse-nonce' ),
			)
		);
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// No frontend assets needed - GTM scripts injected directly.
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Set default settings.
		$default_settings = array(
			'enabled' => false,
			'sgtm' => array(
				'container_id' => '',
				'proxy_path' => '/c/',
				'proxy_timeout' => 15,
				'ip_consent_enabled' => true,
			),
		);

		if ( false === get_option( 'adpulse_settings' ) ) {
			add_option( 'adpulse_settings', $default_settings );
		}

		// Flush rewrite rules for proxy.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

// Initialize the plugin.
AdPulse::get_instance();
