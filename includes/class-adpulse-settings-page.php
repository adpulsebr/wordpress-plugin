<?php
/**
 * AdPulse Settings Page Class
 *
 * @package AdPulse_sGTM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdPulse_Settings_Page
 *
 * Renders the settings page.
 */
class AdPulse_Settings_Page {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse_Settings_Page|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse_Settings_Page
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
		// No hooks needed - render called directly.
	}

	/**
	 * Render settings page
	 */
	public function render() {
		$settings = AdPulse_Settings::get_settings();
		$is_enabled = isset( $settings['enabled'] ) && $settings['enabled'];
		$container_id = isset( $settings['sgtm']['container_id'] ) ? $settings['sgtm']['container_id'] : '';

		// Check if settings were saved.
		$settings_saved = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];
		?>

		<div class="wrap adpulse-settings-page">
			<h1><?php esc_html_e( 'AdPulse sGTM Settings', 'adpulse' ); ?></h1>

			<?php if ( $settings_saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved successfully.', 'adpulse' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $is_enabled && empty( $container_id ) ) : ?>
				<div class="notice notice-warning is-dismissible">
					<p><?php esc_html_e( 'Plugin is enabled but no Container ID is set. Please enter a Container ID to activate tracking.', 'adpulse' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="adpulse-cards">
				<div class="adpulse-card adpulse-card-main">
					<h2><?php esc_html_e( 'Configuration', 'adpulse' ); ?></h2>
					<p><?php esc_html_e( 'Configure your AdPulse sGTM integration below.', 'adpulse' ); ?></p>

					<form action="options.php" method="post">
						<?php
						settings_fields( 'adpulse_settings' );
						do_settings_sections( 'adpulse-settings' );
						submit_button( __( 'Save Settings', 'adpulse' ) );
						?>
					</form>
				</div>

				<div class="adpulse-card adpulse-card-info">
					<h2><?php esc_html_e( 'Status', 'adpulse' ); ?></h2>
					<table class="form-table adpulse-status-table">
						<tr>
							<th><?php esc_html_e( 'Plugin Status', 'adpulse' ); ?></th>
							<td>
								<?php if ( $is_enabled ) : ?>
									<span class="adpulse-status-badge adpulse-status-active">
										<?php esc_html_e( 'Active', 'adpulse' ); ?>
									</span>
								<?php else : ?>
									<span class="adpulse-status-badge adpulse-status-inactive">
										<?php esc_html_e( 'Inactive', 'adpulse' ); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Container ID', 'adpulse' ); ?></th>
							<td>
								<?php if ( $container_id ) : ?>
									<code><?php echo esc_html( $container_id ); ?></code>
								<?php else : ?>
									<em><?php esc_html_e( 'Not configured', 'adpulse' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'GTM URL', 'adpulse' ); ?></th>
							<td>
								<?php if ( $container_id ) : ?>
									<code><?php echo esc_url( site_url( $settings['sgtm']['proxy_path'] . 'gtm.js?id=' . $container_id ) ); ?></code>
								<?php else : ?>
									<em><?php esc_html_e( 'Not configured', 'adpulse' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					</table>

					<h2><?php esc_html_e( 'Quick Links', 'adpulse' ); ?></h2>
					<ul class="adpulse-links">
						<li>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=adpulse-docs' ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'View Documentation', 'adpulse' ); ?>
							</a>
						</li>
						<li>
							<a href="https://dashboard.adpulse.com.br" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'AdPulse Dashboard', 'adpulse' ); ?>
							</a>
						</li>
						<li>
							<a href="https://docs.adpulse.com.br" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'Full Documentation', 'adpulse' ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}
