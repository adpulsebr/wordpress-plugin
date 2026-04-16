<?php
/**
 * AdPulse GTM Manager Class
 *
 * @package AdPulse_sGTM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdPulse_GTM_Manager
 *
 * Manages GTM script injection and data layer population.
 */
class AdPulse_GTM_Manager {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse_GTM_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse_GTM_Manager
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
		add_action( 'wp_head', array( $this, 'inject_gtm_scripts' ), 1 );
		add_action( 'wp_footer', array( $this, 'inject_noscript' ) );
	}

	/**
	 * Inject GTM scripts in head
	 */
	public function inject_gtm_scripts() {
		$settings = AdPulse_Settings::get_settings();

		// Check if enabled.
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		// Check if container ID is set.
		if ( empty( $settings['sgtm']['container_id'] ) ) {
			return;
		}

		// Build GTM URL from WordPress hostname (NOT googletagmanager.com).
		$gtm_url = site_url( $settings['sgtm']['proxy_path'] ) . 'gtm.js';
		$container_id = esc_attr( $settings['sgtm']['container_id'] );

		// Build data layer.
		$data_layer = $this->build_data_layer( $settings );

		// Output GTM scripts.
		?>
		<!-- AdPulse Data Layer -->
		<script data-cfasync="false">
		  window.dataLayer = window.dataLayer || [];
		  window.dataLayer.push(<?php echo wp_json_encode( $data_layer ); ?>);
		</script>
		<!-- Google Tag Manager (First-Party via AdPulse) -->
		<script data-cfasync="false">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		  '<?php echo esc_url( $gtm_url ); ?>?id='+i+dl;f.parentNode.insertBefore(j,f);
		  })(window,document,'script','dataLayer','<?php echo esc_js( $container_id ); ?>');
		</script>
		<?php
	}

	/**
	 * Inject noscript in body
	 */
	public function inject_noscript() {
		$settings = AdPulse_Settings::get_settings();

		// Check if enabled.
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		// Check if container ID is set.
		if ( empty( $settings['sgtm']['container_id'] ) ) {
			return;
		}

		// Build GTM URL.
		$gtm_url = site_url( $settings['sgtm']['proxy_path'] ) . 'gtm.js';
		$container_id = esc_attr( $settings['sgtm']['container_id'] );

		// Output noscript.
		?>
		<!-- Google Tag Manager (NoScript) -->
		<noscript>
		  <iframe src="<?php echo esc_url( $gtm_url ); ?>?id=<?php echo esc_attr( $container_id ); ?>"
		  height="0" width="0" style="display:none;visibility:hidden"></iframe>
		</noscript>
		<?php
	}

	/**
	 * Build data layer
	 *
	 * @param array $settings Plugin settings.
	 * @return array Data layer data.
	 */
	private function build_data_layer( $settings ) {
		$data_layer = array(
			'page' => $this->get_page_data(),
			'user' => $this->get_user_data( $settings ),
			'website' => $this->get_website_data(),
		);

		return $data_layer;
	}

	/**
	 * Get page data
	 *
	 * @return array Page data.
	 */
	private function get_page_data() {
		global $post;

		$page_data = array(
			'type' => $this->get_page_type(),
			'title' => wp_get_document_title(),
			'url' => get_permalink(),
			'language' => get_locale(),
			'template' => get_page_template_slug(),
		);

		// Add post-specific data.
		if ( is_singular() && $post ) {
			$page_data['id'] = $post->ID;
			$page_data['postType'] = get_post_type( $post->ID );
			$page_data['published'] = get_the_date( 'c', $post->ID );
			$page_data['modified'] = get_the_modified_date( 'c', $post->ID );
			$page_data['author'] = get_the_author_meta( 'display_name', $post->post_author );
			$page_data['categories'] = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
			$page_data['tags'] = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
		}

		// Add archive data.
		if ( is_archive() ) {
			if ( is_category() ) {
				$page_data['archiveType'] = 'category';
				$page_data['archiveTitle'] = single_cat_title( '', false );
			} elseif ( is_tag() ) {
				$page_data['archiveType'] = 'tag';
				$page_data['archiveTitle'] = single_tag_title( '', false );
			} elseif ( is_author() ) {
				$page_data['archiveType'] = 'author';
				$page_data['archiveTitle'] = get_the_author_meta( 'display_name', get_query_var( 'author' ) );
			} elseif ( is_date() ) {
				$page_data['archiveType'] = 'date';
			}
		}

		return $page_data;
	}

	/**
	 * Get user data
	 *
	 * @param array $settings Plugin settings.
	 * @return array User data.
	 */
	private function get_user_data( $settings ) {
		$user_data = array(
			'loggedIn' => is_user_logged_in(),
		);

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_data['id'] = $current_user->ID;
			$user_data['username'] = $current_user->user_login;
			$user_data['roles'] = $current_user->roles;
		}

		// Add IP address only if consent is enabled.
		$ip_consent_enabled = isset( $settings['sgtm']['ip_consent_enabled'] ) && $settings['sgtm']['ip_consent_enabled'];

		if ( $ip_consent_enabled && $this->has_consent() ) {
			$user_data['ip'] = $this->get_user_ip();
		}

		return $user_data;
	}

	/**
	 * Get website data
	 *
	 * @return array Website data.
	 */
	private function get_website_data() {
		return array(
			'name' => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'url' => home_url( '/' ),
			'adminEmail' => get_option( 'admin_email' ),
			'charset' => get_bloginfo( 'charset' ),
			'isMultisite' => is_multisite(),
		);
	}

	/**
	 * Get page type
	 *
	 * @return string Page type.
	 */
	private function get_page_type() {
		if ( is_front_page() ) {
			return 'home';
		} elseif ( is_single() ) {
			return 'single';
		} elseif ( is_page() ) {
			return 'page';
		} elseif ( is_category() ) {
			return 'category';
		} elseif ( is_tag() ) {
			return 'tag';
		} elseif ( is_author() ) {
			return 'author';
		} elseif ( is_date() ) {
			return 'date';
		} elseif ( is_search() ) {
			return 'search';
		} elseif ( is_404() ) {
			return '404';
		} elseif ( is_archive() ) {
			return 'archive';
		}

		return 'unknown';
	}

	/**
	 * Get user IP address
	 *
	 * @return string IP address.
	 */
	private function get_user_ip() {
		$ip = '';

		// Check for forwarded headers (behind proxy/load balancer).
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ip = explode( ',', $ip );
			$ip = trim( $ip[0] );
		} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP.
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '';
	}

	/**
	 * Check if user has consent
	 *
	 * @return bool True if consent granted.
	 */
	private function has_consent() {
		// Check for common consent mechanisms.
		// This is a simple implementation - integrate with your consent manager.

		// Check for cookie consent cookie.
		if ( isset( $_COOKIE['cookieconsent_status'] ) && 'allow' === $_COOKIE['cookieconsent_status'] ) {
			return true;
		}

		// Check for OneTrust consent.
		if ( isset( $_COOKIE['OptanonConsent'] ) ) {
			return true;
		}

		// Check for custom consent cookie.
		if ( isset( $_COOKIE['adpulse_consent'] ) && 'granted' === $_COOKIE['adpulse_consent'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get user agent data
	 *
	 * @return array User agent data.
	 */
	public function get_user_agent_data() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return array(
			'userAgent' => $user_agent,
			'platform' => $this->detect_platform( $user_agent ),
			'browser' => $this->detect_browser( $user_agent ),
			'device' => $this->detect_device( $user_agent ),
		);
	}

	/**
	 * Detect platform (OS)
	 *
	 * @param string $user_agent User agent string.
	 * @return string Platform name.
	 */
	private function detect_platform( $user_agent ) {
		$platform = 'Unknown';

		if ( preg_match( '/Windows/i', $user_agent ) ) {
			$platform = 'Windows';
		} elseif ( preg_match( '/Macintosh|Mac OS X/i', $user_agent ) ) {
			$platform = 'Mac OS X';
		} elseif ( preg_match( '/Linux/i', $user_agent ) ) {
			$platform = 'Linux';
		} elseif ( preg_match( '/Android/i', $user_agent ) ) {
			$platform = 'Android';
		} elseif ( preg_match( '/iOS|iPhone|iPad|iPod/i', $user_agent ) ) {
			$platform = 'iOS';
		}

		return $platform;
	}

	/**
	 * Detect browser
	 *
	 * @param string $user_agent User agent string.
	 * @return string Browser name.
	 */
	private function detect_browser( $user_agent ) {
		$browser = 'Unknown';

		if ( preg_match( '/Chrome/i', $user_agent ) && ! preg_match( '/Edge|OPR/i', $user_agent ) ) {
			$browser = 'Chrome';
		} elseif ( preg_match( '/Firefox/i', $user_agent ) ) {
			$browser = 'Firefox';
		} elseif ( preg_match( '/Safari/i', $user_agent ) && ! preg_match( '/Chrome/i', $user_agent ) ) {
			$browser = 'Safari';
		} elseif ( preg_match( '/Edge/i', $user_agent ) ) {
			$browser = 'Edge';
		} elseif ( preg_match( '/OPR/i', $user_agent ) ) {
			$browser = 'Opera';
		} elseif ( preg_match( '/MSIE|Trident/i', $user_agent ) ) {
			$browser = 'Internet Explorer';
		}

		return $browser;
	}

	/**
	 * Detect device type
	 *
	 * @param string $user_agent User agent string.
	 * @return string Device type.
	 */
	private function detect_device( $user_agent ) {
		$device = 'Desktop';

		if ( preg_match( '/Mobile|Android|iPhone|iPad|iPod/i', $user_agent ) ) {
			$device = 'Mobile';
		} elseif ( preg_match( '/Tablet|iPad/i', $user_agent ) ) {
			$device = 'Tablet';
		}

		return $device;
	}
}
