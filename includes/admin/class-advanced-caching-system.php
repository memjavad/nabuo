<?php
/**
 * Advanced Caching System
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Advanced_Caching_System class - Intelligent caching for performance.
 */
class Advanced_Caching_System {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Cache prefix
	 *
	 * @var string
	 */
	private $cache_prefix = 'naboo_cache_';

	/**
	 * Cache expiration (in seconds)
	 *
	 * @var int
	 */
	private $cache_duration = 3600; // 1 hour

	/**
	 * Constructor
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->create_table();
	}

	/**
	 * Create cache table
	 */
	private function create_table() {
		// No longer needed, as we use native Object Cache API now.
		// Kept empty to avoid breaking older plugin activation hooks.
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/cache/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cache_stats' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/cache/clear',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/cache/warm',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'warm_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/cache/set',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/cache/get',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_cache' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get cache statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_cache_stats( $request ) {
		$stats = array(
			'message' => 'Cache is managed by WordPress Object Cache. See Drop-in cache stats for details.'
		);

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Clear cache
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function clear_cache( $request ) {
		wp_cache_flush();
		
		return new \WP_REST_Response( array( 'message' => 'WordPress Object Cache flushed completely' ), 200 );
	}

	/**
	 * Warm cache with common queries
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function warm_cache( $request ) {
		global $wpdb;

		// Cache top scales
		$top_scales = $wpdb->get_results(
			"SELECT p.ID, p.post_title FROM {$wpdb->posts} p
			JOIN {$wpdb->prefix}naboo_popularity_analytics pa ON p.ID = pa.scale_id
			WHERE p.post_type = 'psych_scale' AND p.post_status = 'publish'
			ORDER BY pa.views DESC LIMIT 20"
		);

		$cached = 0;
		foreach ( $top_scales as $scale ) {
			$this->cache_set(
				'scale_' . $scale->ID,
				$scale,
				'scale',
				3600 * 24 // 24 hours
			);
			$cached++;
		}

		// Cache categories
		$categories = get_terms( array( 'taxonomy' => 'scale_category' ) );
		foreach ( $categories as $cat ) {
			$this->cache_set(
				'category_' . $cat->term_id,
				$cat,
				'category',
				3600 * 24
			);
			$cached++;
		}

		// Cache popular searches
		$searches = $wpdb->get_results(
			"SELECT search_query, COUNT(*) as count FROM {$wpdb->prefix}naboo_search_analytics
			GROUP BY search_query ORDER BY count DESC LIMIT 10"
		);

		foreach ( $searches as $search ) {
			$this->cache_set(
				'search_' . md5( $search->search_query ),
				$search,
				'search',
				3600 * 24
			);
			$cached++;
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d cache entries warmed', $cached ) ),
			200
		);
	}

	/**
	 * Set cache entry
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function set_cache( $request ) {
		$cache_key = sanitize_key( $request->get_param( 'cache_key' ) );
		$cache_value = $request->get_param( 'cache_value' );
		$cache_type = sanitize_text_field( $request->get_param( 'cache_type' ) ?? 'general' );
		$duration = (int) $request->get_param( 'duration' ) ?? 3600;

		$this->cache_set( $cache_key, $cache_value, $cache_type, $duration );

		return new \WP_REST_Response(
			array( 'message' => 'Cache entry set' ),
			200
		);
	}

	/**
	 * Get cache entry
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_cache( $request ) {
		$cache_key = sanitize_key( $request->get_param( 'cache_key' ) );

		$value = $this->cache_get( $cache_key );

		if ( $value !== null ) {
			return new \WP_REST_Response( array( 'data' => $value ), 200 );
		}

		return new \WP_REST_Response( array( 'data' => null ), 200 );
	}

	/**
	 * Store data in cache
	 *
	 * @param string $key      Cache key.
	 * @param mixed  $value    Cache value.
	 * @param string $type     Cache type.
	 * @param int    $duration Duration in seconds.
	 */
	public function cache_set( $key, $value, $type = 'general', $duration = 3600 ) {
		wp_cache_set( $key, $value, $this->cache_prefix . $type, $duration );
	}

	/**
	 * Retrieve data from cache
	 *
	 * @param string $key Cache key.
	 * @return mixed|null
	 */
	public function cache_get( $key ) {
		// the type is unknown here, but usually callers pass type in set but not get. 
		// For backward compatibility, we will assume they use the unified WP cache functions directly eventually, 
		// but since we need 'group', we will default to 'general' or try without group
		return wp_cache_get( $key, $this->cache_prefix . 'general' ) ?: wp_cache_get( $key );
	}

	/**
	 * Delete cache entry
	 *
	 * @param string $key Cache key.
	 */
	public function cache_delete( $key ) {
		// As type isn't known here, we only flush general pool or without group.
		wp_cache_delete( $key, $this->cache_prefix . 'general' );
		wp_cache_delete( $key );
	}

	/**
	 * Clear expired cache
	 */
	public function clear_expired() {
		// Object Cache handles TTL evicitons natively.
	}
}
