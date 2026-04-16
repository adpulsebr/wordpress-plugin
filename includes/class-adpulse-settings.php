<?php
/**
 * AdPulse Settings Class
 *
 * @package AdPulse_sGTM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdPulse_Settings
 *
 * Manages plugin settings using WordPress Settings API.
 */
class AdPulse_Settings {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse_Settings
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
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'register_sections' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'enabled' => false,
			'sgtm' => array(
				'container_id' => '',
				'proxy_path' => '/c/',
				'proxy_timeout' => 15,
				'ip_consent_enabled' => true,
			),
		);
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = self::get_defaults();
		$settings = get_option( 'adpulse_settings', array() );

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'adpulse_settings',
			'adpulse_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default' => self::get_defaults(),
			)
		);
	}

	/**
	 * Register settings sections
	 */
	public function register_sections() {
		add_settings_section(
			'adpulse_general_section',
			__( 'General Settings', 'adpulse' ),
			array( $this, 'general_section_callback' ),
			'adpulse-settings'
		);

		add_settings_section(
			'adpulse_sgtm_section',
			__( 'Server GTM Settings', 'adpulse' ),
			array( $this, 'sgtm_section_callback' ),
			'adpulse-settings'
		);
	}

	/**
	 * Register settings fields
	 */
	public function register_fields() {
		// General section fields.
		add_settings_field(
			'adpulse_enabled',
			__( 'Enable AdPulse', 'adpulse' ),
			array( $this, 'render_enabled_field' ),
			'adpulse-settings',
			'adpulse_general_section'
		);

		// sGTM section fields.
		add_settings_field(
			'adpulse_sgtm_container_id',
			__( 'Container ID', 'adpulse' ),
			array( $this, 'render_container_id_field' ),
			'adpulse-settings',
			'adpulse_sgtm_section'
		);

		add_settings_field(
			'adpulse_sgtm_proxy_path',
			__( 'Proxy Path', 'adpulse' ),
			array( $this, 'render_proxy_path_field' ),
			'adpulse-settings',
			'adpulse_sgtm_section'
		);

		add_settings_field(
			'adpulse_sgtm_proxy_timeout',
			__( 'Proxy Timeout (seconds)', 'adpulse' ),
			array( $this, 'render_proxy_timeout_field' ),
			'adpulse-settings',
			'adpulse_sgtm_section'
		);

		add_settings_field(
			'adpulse_sgtm_ip_consent_enabled',
			__( 'Include IP Address with Consent', 'adpulse' ),
			array( $this, 'render_ip_consent_field' ),
			'adpulse-settings',
			'adpulse_sgtm_section'
		);
	}

	/**
	 * General section callback
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure general plugin settings.', 'adpulse' ) . '</p>';
	}

	/**
	 * sGTM section callback
	 */
	public function sgtm_section_callback() {
		echo '<p>' . esc_html__( 'Configure server-side GTM integration. Get your Container ID from the AdPulse dashboard.', 'adpulse' ) . '</p>';
	}

	/**
	 * Render enabled field
	 */
	public function render_enabled_field() {
		$settings = self::get_settings();
		$enabled = isset( $settings['enabled'] ) ? $settings['enabled'] : false;
		?>

		<label>
			<input type="checkbox" name="adpulse_settings[enabled]" value="1" <?php checked( $enabled, true ); ?>>
			<?php esc_html_e( 'Enable AdPulse sGTM integration', 'adpulse' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, GTM scripts will be injected into your pages.', 'adpulse' ); ?></p>
		<?php
	}

	/**
	 * Render container ID field
	 */
	public function render_container_id_field() {
		$settings = self::get_settings();
		$container_id = isset( $settings['sgtm']['container_id'] ) ? $settings['sgtm']['container_id'] : '';
		?>

		<input
			type="text"
			name="adpulse_settings[sgtm][container_id]"
			value="<?php echo esc_attr( $container_id ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g., 12345', 'adpulse' ); ?>"
		>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Dashboard URL */
				esc_html__( 'Enter the numeric Container ID from your %s.', 'adpulse' ),
				'<a href="https://dashboard.adpulse.com.br" target="_blank">' . esc_html__( 'AdPulse dashboard', 'adpulse' ) . '</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render proxy path field
	 */
	public function render_proxy_path_field() {
		$settings = self::get_settings();
		$proxy_path = isset( $settings['sgtm']['proxy_path'] ) ? $settings['sgtm']['proxy_path'] : '/c/';
		?>

		<input
			type="text"
			name="adpulse_settings[sgtm][proxy_path]"
			value="<?php echo esc_attr( $proxy_path ); ?>"
			class="regular-text"
			placeholder="/c/"
		>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Example URL */
				esc_html__( 'The path where GTM will be served. Example: %s', 'adpulse' ),
				'<code>' . esc_url( site_url( '/c/gtm.js' ) ) . '</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render proxy timeout field
	 */
	public function render_proxy_timeout_field() {
		$settings = self::get_settings();
		$proxy_timeout = isset( $settings['sgtm']['proxy_timeout'] ) ? $settings['sgtm']['proxy_timeout'] : 15;
		?>

		<input
			type="number"
			name="adpulse_settings[sgtm][proxy_timeout]"
			value="<?php echo esc_attr( $proxy_timeout ); ?>"
			class="small-text"
			min="1"
			max="60"
		>
		<p class="description"><?php esc_html_e( 'Maximum time (in seconds) to wait for the sGTM server to respond.', 'adpulse' ); ?></p>
		<?php
	}

	/**
	 * Render IP consent field
	 */
	public function render_ip_consent_field() {
		$settings = self::get_settings();
		$ip_consent_enabled = isset( $settings['sgtm']['ip_consent_enabled'] ) ? $settings['sgtm']['ip_consent_enabled'] : true;
		?>

		<label>
			<input type="checkbox" name="adpulse_settings[sgtm][ip_consent_enabled]" value="1" <?php checked( $ip_consent_enabled, true ); ?>>
			<?php esc_html_e( 'Include visitor IP address when consent is granted', 'adpulse' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'IP address will only be sent to GTM when user has granted consent.', 'adpulse' ); ?></p>
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = self::get_defaults();

		// Sanitize enabled field.
		$sanitized['enabled'] = isset( $input['enabled'] ) && '1' === $input['enabled'];

		// Sanitize sGTM settings.
		if ( isset( $input['sgtm'] ) && is_array( $input['sgtm'] ) ) {
			// Container ID: numeric only.
			$sanitized['sgtm']['container_id'] = preg_replace( '/[^0-9]/', '', $input['sgtm']['container_id'] ?? '' );

			// Proxy path: ensure starts and ends with slash.
			$proxy_path = isset( $input['sgtm']['proxy_path'] ) ? $input['sgtm']['proxy_path'] : '/c/';
			$proxy_path = '/' . trim( $proxy_path, '/' ) . '/';
			$sanitized['sgtm']['proxy_path'] = $proxy_path;

			// Proxy timeout: between 1 and 60 seconds.
			$proxy_timeout = isset( $input['sgtm']['proxy_timeout'] ) ? intval( $input['sgtm']['proxy_timeout'] ) : 15;
			$proxy_timeout = max( 1, min( 60, $proxy_timeout ) );
			$sanitized['sgtm']['proxy_timeout'] = $proxy_timeout;

			// IP consent: boolean.
			$sanitized['sgtm']['ip_consent_enabled'] = isset( $input['sgtm']['ip_consent_enabled'] ) && '1' === $input['sgtm']['ip_consent_enabled'];
		}

		return $sanitized;
	}
}
