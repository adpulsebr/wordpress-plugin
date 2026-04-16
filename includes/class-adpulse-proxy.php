<?php
/**
 * AdPulse Proxy Class
 *
 * @package AdPulse_sGTM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdPulse_Proxy
 *
 * Handles first-party proxy requests to sGTM server.
 */
class AdPulse_Proxy {

	/**
	 * Single instance of the class
	 *
	 * @var AdPulse_Proxy|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AdPulse_Proxy
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
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'handle_proxy_request' ) );
	}

	/**
	 * Add rewrite rules for proxy
	 */
	public function add_rewrite_rules() {
		$settings = AdPulse_Settings::get_settings();
		$proxy_path = isset( $settings['sgtm']['proxy_path'] ) ? $settings['sgtm']['proxy_path'] : '/c/';

		// Remove leading/trailing slashes for regex.
		$path_pattern = trim( $proxy_path, '/' );
		if ( empty( $path_pattern ) ) {
			$path_pattern = 'c';
		}

		add_rewrite_rule(
			'^' . $path_pattern . '/(.*)$',
			'index.php?adpulse_proxy=1&adpulse_proxy_path=$matches[1]',
			'top'
		);

		add_rewrite_tag( '%adpulse_proxy%', '([01])' );
		add_rewrite_tag( '%adpulse_proxy_path%', '(.+)' );
	}

	/**
	 * Handle proxy request
	 */
	public function handle_proxy_request() {
		if ( ! get_query_var( 'adpulse_proxy' ) ) {
			return;
		}

		$settings = AdPulse_Settings::get_settings();

		if ( empty( $settings['enabled'] ) ) {
			wp_die( esc_html__( 'AdPulse sGTM is not enabled.', 'adpulse' ), esc_html__( 'Not Found', 'adpulse' ), 404 );
		}

		if ( empty( $settings['sgtm']['container_id'] ) ) {
			wp_die( esc_html__( 'Container ID not configured.', 'adpulse' ), esc_html__( 'Configuration Error', 'adpulse' ), 500 );
		}

		$proxy_path = get_query_var( 'adpulse_proxy_path' );
		$this->proxy_request( $proxy_path );
	}

	/**
	 * Proxy request to sGTM server
	 *
	 * @param string $path Request path.
	 */
	private function proxy_request( $path ) {
		$settings = AdPulse_Settings::get_settings();
		$container_id = $settings['sgtm']['container_id'];
		$timeout = isset( $settings['sgtm']['proxy_timeout'] ) ? $settings['sgtm']['proxy_timeout'] : 15;

		// Build sGTM server URL.
		// In production, this should be your sGTM server domain.
		$sgtm_server = 'https://gtm.adpulse.com.br';
		$sgtm_url = $sgtm_server . '/c' . $container_id . '/' . $path;

		// Prepare request headers.
		$headers = $this->prepare_request_headers();

		// Get request method.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';

		// Get request body for POST requests.
		$body = null;
		if ( 'POST' === $method || 'PUT' === $method ) {
			$body = file_get_contents( 'php://input' );
		}

		// Build query string.
		$query_string = $_SERVER['QUERY_STRING'] ?? '';
		if ( ! empty( $query_string ) ) {
			$sgtm_url .= '?' . $query_string;
		}

		// Make request to sGTM server.
		$response = wp_remote_request(
			$sgtm_url,
			array(
				'method' => $method,
				'headers' => $headers,
				'body' => $body,
				'timeout' => $timeout,
				'sslverify' => true,
			)
		);

		// Handle errors.
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			wp_die(
				esc_html( sprintf( __( 'Proxy error: %s', 'adpulse' ), $error_message ) ),
				esc_html__( 'Proxy Error', 'adpulse' ),
				502
			);
		}

		// Get response code and headers.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Rewrite cookies as first-party.
		$response_headers = $this->rewrite_cookies( $response_headers );

		// Send response.
		$this->send_response( $response_code, $response_headers, $response_body );
	}

	/**
	 * Prepare request headers
	 *
	 * @return array
	 */
	private function prepare_request_headers() {
		$headers = array();

		// Get all request headers.
		$request_headers = $this->get_request_headers();

		// Filter and forward relevant headers.
		$allowed_headers = array(
			'content-type',
			'content-length',
			'user-agent',
			'accept',
			'accept-language',
			'accept-encoding',
			'cache-control',
		);

		foreach ( $allowed_headers as $header ) {
			$header_key = 'HTTP_' . strtoupper( str_replace( '-', '_', $header ) );
			if ( isset( $request_headers[ $header_key ] ) ) {
				$headers[ $header ] = $request_headers[ $header_key ];
			}
		}

		return $headers;
	}

	/**
	 * Get request headers
	 *
	 * @return array
	 */
	private function get_request_headers() {
		$headers = array();

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
		} else {
			foreach ( $_SERVER as $key => $value ) {
				if ( 0 === strpos( $key, 'HTTP_' ) ) {
					$header = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
					$headers[ $header ] = $value;
				}
			}
		}

		return $headers;
	}

	/**
	 * Rewrite cookies as first-party
	 *
	 * @param array $response_headers Response headers.
	 * @return array Modified response headers.
	 */
	private function rewrite_cookies( $response_headers ) {
		if ( ! is_array( $response_headers ) ) {
			$response_headers = (array) $response_headers;
		}

		$site_domain = $_SERVER['HTTP_HOST'] ?? '';
		$site_path = parse_url( home_url(), PHP_URL_PATH ) ?: '/';

		if ( isset( $response_headers['set-cookie'] ) ) {
			$cookies = $response_headers['set-cookie'];

			if ( ! is_array( $cookies ) ) {
				$cookies = array( $cookies );
			}

			$rewritten_cookies = array();
			foreach ( $cookies as $cookie ) {
				$cookie_parts = $this->parse_cookie_string( $cookie );
				$cookie_parts['domain'] = $site_domain;
				$cookie_parts['path'] = $site_path;
				$cookie_parts['samesite'] = 'Lax';
				$cookie_parts['secure'] = is_ssl();
				$rewritten_cookies[] = $this->build_cookie_string( $cookie_parts );
			}

			$response_headers['set-cookie'] = $rewritten_cookies;
		}

		return $response_headers;
	}

	/**
	 * Parse cookie string
	 *
	 * @param string $cookie Cookie string.
	 * @return array Parsed cookie parts.
	 */
	private function parse_cookie_string( $cookie ) {
		$parts = array(
			'name' => '',
			'value' => '',
			'expires' => '',
			'max-age' => '',
			'domain' => '',
			'path' => '/',
			'secure' => false,
			'httponly' => false,
			'samesite' => '',
		);

		$attributes = explode( ';', $cookie );

		// First part is name=value.
		if ( ! empty( $attributes[0] ) ) {
			$name_value = explode( '=', $attributes[0], 2 );
			$parts['name'] = trim( $name_value[0] );
			$parts['value'] = isset( $name_value[1] ) ? trim( $name_value[1] ) : '';
		}

		// Parse attributes.
		for ( $i = 1; $i < count( $attributes ); $i++ ) {
			$attr = trim( $attributes[ $i ] );

			if ( empty( $attr ) ) {
				continue;
			}

			$attr_parts = explode( '=', $attr, 2 );
			$attr_name = strtolower( trim( $attr_parts[0] ) );

			if ( 'expires' === $attr_name ) {
				$parts['expires'] = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '';
			} elseif ( 'max-age' === $attr_name ) {
				$parts['max-age'] = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '';
			} elseif ( 'domain' === $attr_name ) {
				$parts['domain'] = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '';
			} elseif ( 'path' === $attr_name ) {
				$parts['path'] = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '/';
			} elseif ( 'secure' === $attr_name ) {
				$parts['secure'] = true;
			} elseif ( 'httponly' === $attr_name ) {
				$parts['httponly'] = true;
			} elseif ( 'samesite' === $attr_name ) {
				$parts['samesite'] = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '';
			}
		}

		return $parts;
	}

	/**
	 * Build cookie string
	 *
	 * @param array $parts Cookie parts.
	 * @return string Cookie string.
	 */
	private function build_cookie_string( $parts ) {
		$cookie = $parts['name'] . '=' . $parts['value'];

		if ( ! empty( $parts['expires'] ) ) {
			$cookie .= '; Expires=' . $parts['expires'];
		}

		if ( ! empty( $parts['max-age'] ) ) {
			$cookie .= '; Max-Age=' . $parts['max-age'];
		}

		$cookie .= '; Domain=' . $parts['domain'];
		$cookie .= '; Path=' . $parts['path'];

		if ( $parts['secure'] ) {
			$cookie .= '; Secure';
		}

		if ( $parts['httponly'] ) {
			$cookie .= '; HttpOnly';
		}

		if ( ! empty( $parts['samesite'] ) ) {
			$cookie .= '; SameSite=' . $parts['samesite'];
		}

		return $cookie;
	}

	/**
	 * Send response to client
	 *
	 * @param int    $code HTTP status code.
	 * @param array  $headers Response headers.
	 * @param string $body Response body.
	 */
	private function send_response( $code, $headers, $body ) {
		// Set status code.
		status_header( $code );

		// Send headers.
		foreach ( $headers as $name => $value ) {
			if ( 'set-cookie' === strtolower( $name ) && is_array( $value ) ) {
				foreach ( $value as $cookie ) {
					header( $name . ': ' . $cookie, false );
				}
			} else {
				header( $name . ': ' . $value );
			}
		}

		// Send body and exit.
		echo $body;
		exit;
	}
}
