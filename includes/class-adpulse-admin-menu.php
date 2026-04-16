<?php
/**
 * AdPulse Admin Menu Class
 *
 * @package AdPulse_sGTM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdPulse_Admin_Menu
 *
 * Registers the admin menu and submenu items.
 */
class AdPulse_Admin_Menu {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse_Admin_Menu|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse_Admin_Menu
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register admin menu
	 */
	public function register_menu() {
		add_menu_page(
			__( 'AdPulse sGTM', 'adpulse' ),
			__( 'AdPulse', 'adpulse' ),
			'manage_options',
			'adpulse-settings',
			array( $this, 'display_settings_page' ),
			'dashicons-analytics',
			100
		);

		add_submenu_page(
			'adpulse-settings',
			__( 'Settings', 'adpulse' ),
			__( 'Settings', 'adpulse' ),
			'manage_options',
			'adpulse-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'adpulse-settings',
			__( 'Documentation', 'adpulse' ),
			__( 'Documentation', 'adpulse' ),
			'manage_options',
			'adpulse-docs',
			array( $this, 'display_docs_page' )
		);
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'adpulse' ) );
		}

		// Get settings page instance and display.
		$settings_page = AdPulse_Settings_Page::get_instance();
		$settings_page->render();
	}

	/**
	 * Display documentation page
	 */
	public function display_docs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'adpulse' ) );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AdPulse sGTM Documentation', 'adpulse' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Getting Started', 'adpulse' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Create a container in your AdPulse dashboard', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'Copy the Container ID', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'Enter the Container ID in the Settings page', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'Enable the plugin', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'Your GTM container will now load from your own domain', 'adpulse' ); ?></li>
				</ol>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'How It Works', 'adpulse' ); ?></h2>
				<p><?php esc_html_e( 'AdPulse sGTM uses a first-party proxy to route all Google Tag Manager traffic through your own domain:', 'adpulse' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'GTM scripts load from your domain (e.g., yourdomain.com/c/gtm.js)', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'All cookies are set as first-party (SameSite=Lax)', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'Data layer is populated on the server-side (PHP)', 'adpulse' ); ?></li>
					<li><?php esc_html_e( 'No third-party requests to googletagmanager.com', 'adpulse' ); ?></li>
				</ul>
			</div>

			<div class="card" style="margin-top: 20px;">
				<h2><?php esc_html_e( 'Support', 'adpulse' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: Documentation URL */
						esc_html__( 'For more information, visit our %s.', 'adpulse' ),
						'<a href="https://docs.adpulse.com.br" target="_blank">' . esc_html__( 'documentation', 'adpulse' ) . '</a>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}
}
