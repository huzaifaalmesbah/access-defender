<?php
/**
 * Base API Provider Class
 *
 * Abstract base class for all API providers
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.0
 */

namespace AccessDefender\Services\ApiProviders;

use AccessDefender\Interfaces\ApiProviderInterface;

/**
 * Abstract BaseProvider Class
 *
 * Provides common functionality for all API providers
 */
abstract class BaseProvider implements ApiProviderInterface {

	/**
	 * Provider configuration
	 *
	 * @var array
	 */
	protected $config = array();

	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	protected $cache_prefix = 'accessdefender_';

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	protected $cache_expiration = 3600; // 1 hour

	/**
	 * Request timeout in seconds
	 *
	 * @var int
	 */
	protected $timeout = 10;

	/**
	 * Constructor
	 *
	 * @param array $config Provider configuration
	 */
	public function __construct( array $config = array() ) {
		$this->config = $config;
	}

	/**
	 * Make HTTP request
	 *
	 * @param string $url Request URL
	 * @param array  $args Request arguments
	 * @return array|false Response data or false on failure
	 */
	protected function make_request( string $url, array $args = array() ): array {
		$default_args = array(
			'timeout'    => $this->timeout,
			'user-agent' => 'Access Defender WordPress Plugin/' . ACCESS_DEFENDER_VERSION,
		);

		$args = wp_parse_args( $args, $default_args );

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return array(
				'success' => false,
				'error'   => "HTTP {$response_code}: {$body}",
			);
		}

		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array(
				'success' => false,
				'error'   => 'Invalid JSON response',
			);
		}

		return array(
			'success' => true,
			'data'    => $data,
		);
	}

	/**
	 * Get cached result
	 *
	 * @param string $ip IP address
	 * @return array|false Cached data or false if not found
	 */
	protected function get_cached_result( string $ip ) {
		$cache_key = $this->cache_prefix . $this->get_slug() . '_' . md5( $ip );
		return get_transient( $cache_key );
	}

	/**
	 * Cache result
	 *
	 * @param string $ip IP address
	 * @param array  $data Data to cache
	 * @return void
	 */
	protected function cache_result( string $ip, array $data ): void {
		$cache_key = $this->cache_prefix . $this->get_slug() . '_' . md5( $ip );
		set_transient( $cache_key, $data, $this->cache_expiration );
	}

	/**
	 * Log API usage
	 *
	 * @param bool $success Whether request was successful
	 * @return void
	 */
	protected function log_usage( bool $success ): void {
		// Monthly usage tracking
		$usage_key = $this->cache_prefix . 'usage_' . $this->get_slug() . '_' . date( 'Y-m' );
		$usage     = get_option( $usage_key, 0 );
		$usage++;
		update_option( $usage_key, $usage );

		// Per-minute usage tracking for rate limiting
		// Only track minute-level counters for free providers (e.g., IP-API free)
		if ( method_exists( $this, 'is_free' ) && $this->is_free() ) {
			$minute_key   = $this->cache_prefix . 'minute_' . $this->get_slug() . '_' . date( 'Y-m-d-H-i' );
			$minute_usage = get_option( $minute_key, 0 );
			$minute_usage++;
			update_option( $minute_key, $minute_usage, 120 ); // Expire after 2 minutes
		}

		// Log success/failure rate
		$stats_key = $this->cache_prefix . 'stats_' . $this->get_slug();
		$stats     = get_option( $stats_key, array( 'success' => 0, 'failed' => 0 ) );

		if ( $success ) {
			$stats['success']++;
		} else {
			$stats['failed']++;
		}

		update_option( $stats_key, $stats );
	}

	/**
	 * Get usage statistics
	 *
	 * @return array Usage statistics
	 */
	public function get_usage_stats(): array {
		$usage_key = $this->cache_prefix . 'usage_' . $this->get_slug() . '_' . date( 'Y-m' );
		$stats_key = $this->cache_prefix . 'stats_' . $this->get_slug();

		$usage = get_option( $usage_key, 0 );
		$stats = get_option( $stats_key, array( 'success' => 0, 'failed' => 0 ) );

		$total_requests = $stats['success'] + $stats['failed'];
		$success_rate   = $total_requests > 0 ? round( ( $stats['success'] / $total_requests ) * 100, 2 ) : 0;

		return array(
			'monthly_usage' => $usage,
			'total_success' => $stats['success'],
			'total_failed'  => $stats['failed'],
			'success_rate'  => $success_rate,
		);
	}

	/**
	 * Check if provider has reached per-minute rate limit
	 *
	 * @return bool True if rate limited
	 */
	public function is_minute_rate_limited(): bool {
		// Minute rate limiting applies only to free providers
		if ( ! ( method_exists( $this, 'is_free' ) && $this->is_free() ) ) {
			return false;
		}

		$minute_limit = $this->get_minute_rate_limit();
		if ( $minute_limit === 0 ) {
			return false; // No limit
		}

		$minute_key   = $this->cache_prefix . 'minute_' . $this->get_slug() . '_' . date( 'Y-m-d-H-i' );
		$minute_usage = get_option( $minute_key, 0 );

		return $minute_usage >= $minute_limit;
	}

	/**
	 * Get per-minute rate limit (to be overridden by providers)
	 *
	 * @return int Per-minute rate limit (0 for no limit)
	 */
	public function get_minute_rate_limit(): int {
		return 0; // Default: no per-minute limit
	}

	/**
	 * Default status implementation
	 *
	 * @return array Status information
	 */
	public function get_status(): array {
		$stats = $this->get_usage_stats();

		return array(
			'provider'      => $this->get_name(),
			'slug'          => $this->get_slug(),
			'is_free'       => $this->is_free(),
			'rate_limit'    => $this->get_rate_limit(),
			'monthly_usage' => $stats['monthly_usage'],
			'total_success' => $stats['total_success'],
			'total_failed'  => $stats['total_failed'],
			'success_rate'  => $stats['success_rate'],
			'status'        => $stats['success_rate'] > 80 ? 'healthy' : 'degraded',
		);
	}
}
