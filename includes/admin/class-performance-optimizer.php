<?php
/**
 * Performance Optimizer - Orchestrator
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Performance\Performance_Cleaner;
use ArabPsychology\NabooDatabase\Admin\Performance\Asset_Consolidator;
use ArabPsychology\NabooDatabase\Admin\Performance\Cloudflare_Manager;
use ArabPsychology\NabooDatabase\Admin\Performance\Page_Cache_Manager;

/**
 * Performance_Optimizer class
 */
class Performance_Optimizer {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	/** @var string */
	private $option_name = 'naboodatabase_performance_options';

	/** @var Performance_Cleaner */
	private $cleaner;

	/** @var Asset_Consolidator */
	private $consolidator;

	/** @var Cloudflare_Manager */
	private $cloudflare;

	/** @var Page_Cache_Manager */
	private $page_cache;

	/**
	 * Constructor
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->cleaner      = new Performance_Cleaner();
		$this->consolidator = new Asset_Consolidator();
		$this->cloudflare   = new Cloudflare_Manager();
		$this->page_cache   = new Page_Cache_Manager();
	}

	/**
	 * Register admin menu
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Performance', 'naboodatabase' ),
			__( '⚡ Performance', 'naboodatabase' ),
			'manage_options',
			'naboodatabase-performance',
			array( $this, 'render_admin_page' ),
			8
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'naboodatabase_performance_group', $this->option_name, array( $this, 'sanitize_options' ) );
	}

	/**
	 * Initialize optimizations based on settings
	 */
	public function init_optimizations() {
		$options = get_option( $this->option_name, array() );

		// Core Bloat
		if ( ! empty( $options['disable_emojis'] ) ) {
			add_action( 'init', array( $this->cleaner, 'disable_emojis' ) );
		}
		if ( ! empty( $options['disable_embeds'] ) ) {
			add_action( 'init', array( $this->cleaner, 'disable_embeds' ), 9999 );
		}
		if ( ! empty( $options['remove_query_strings'] ) ) {
			add_filter( 'style_loader_src', array( $this->cleaner, 'remove_query_strings' ), 10, 2 );
			add_filter( 'script_loader_src', array( $this->cleaner, 'remove_query_strings' ), 10, 2 );
		}
		if ( ! empty( $options['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', '__return_empty_array' );
		}
		if ( ! empty( $options['disable_block_css'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this->cleaner, 'disable_block_css' ), 100 );
		}
		if ( ! empty( $options['disable_jquery_migrate'] ) ) {
			add_action( 'wp_default_scripts', array( $this->cleaner, 'disable_jquery_migrate_action' ) );
		}
		if ( ! empty( $options['disable_heartbeat'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this->cleaner, 'disable_heartbeat_action' ), 1 );
		}
		if ( ! empty( $options['disable_author_pages'] ) ) {
			add_action( 'template_redirect', array( $this->cleaner, 'disable_author_pages_action' ) );
		}
		if ( ! empty( $options['disable_attachment_pages'] ) ) {
			add_action( 'template_redirect', array( $this->cleaner, 'disable_attachment_pages_action' ) );
		}
		if ( ! empty( $options['remove_rest_links'] ) ) {
			add_action( 'init', array( $this->cleaner, 'remove_rest_links_action' ) );
		}
		if ( ! empty( $options['disable_global_styles'] ) ) {
			add_action( 'init', array( $this->cleaner, 'disable_global_styles_action' ) );
		}
		if ( ! empty( $options['defer_js'] ) ) {
			add_filter( 'script_loader_tag', array( $this->cleaner, 'defer_js_scripts' ), 10, 3 );
		}
		if ( ! empty( $options['clean_head'] ) ) {
			add_action( 'init', array( $this->cleaner, 'clean_head_tags' ) );
		}

		// Comments
		if ( ! empty( $options['disable_native_comments'] ) ) {
			add_action( 'admin_init', array( $this->cleaner, 'disable_comments_admin_menu_redirect' ) );
			add_filter( 'comments_open', '__return_false', 20, 2 );
			add_filter( 'pings_open', '__return_false', 20, 2 );
			add_action( 'admin_menu', array( $this->cleaner, 'remove_comments_admin_menu' ) );
			add_action( 'init', array( $this->cleaner, 'disable_comments_support_action' ), 100 );
			add_action( 'wp_before_admin_bar_render', array( $this->cleaner, 'remove_comments_admin_bar' ) );
		}

		// Assets
		if ( ! empty( $options['consolidate_assets'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this->consolidator, 'enqueue_consolidated_assets' ), 999 );
		}
		if ( ! empty( $options['disable_theme_assets'] ) ) {
			add_action( 'wp_enqueue_scripts', array( $this->consolidator, 'dequeue_theme_assets_action' ), 99 );
		}

		// Cloudflare
		add_action( 'wp_ajax_naboo_cf_whitelist_ip', array( $this->cloudflare, 'ajax_cf_whitelist_ip' ) );
		add_action( 'wp_ajax_naboo_purge_cloudflare_all', array( $this->cloudflare, 'ajax_purge_cloudflare_all' ) );
		add_action( 'wp_ajax_naboo_deploy_cloudflare_worker', array( $this->cloudflare, 'ajax_deploy_cloudflare_worker' ) );
		add_action( 'wp_ajax_naboo_cf_create_cache_rule', array( $this->cloudflare, 'ajax_cf_create_cache_rule' ) );
		add_action( 'update_option_' . $this->option_name, array( $this->cloudflare, 'sync_cloudflare_settings_on_save' ), 10, 3 );

		// Cache
		add_action( 'wp_ajax_naboo_clear_asset_cache', array( $this->consolidator, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_naboo_install_object_cache', array( $this->page_cache, 'ajax_install_object_cache' ) );
		add_action( 'wp_ajax_naboo_uninstall_object_cache', array( $this->page_cache, 'ajax_uninstall_object_cache' ) );
		add_action( 'wp_ajax_naboo_install_page_cache', array( $this->page_cache, 'ajax_install_page_cache' ) );
		add_action( 'wp_ajax_naboo_uninstall_page_cache', array( $this->page_cache, 'ajax_uninstall_page_cache' ) );
		add_action( 'wp_ajax_naboo_clear_page_cache', array( $this->page_cache, 'ajax_clear_page_cache' ) );
		add_action( 'update_option_' . $this->option_name, array( $this->page_cache, 'write_page_cache_config' ), 10, 3 );

		// HTML Minification
		if ( ! empty( $options['minify_html'] ) ) {
			add_action( 'template_redirect', array( $this, 'minify_html_start' ), 1 );
		}

		// Preloads
		if ( ! empty( $options['preload_assets'] ) ) {
			add_action( 'wp_head', array( $this, 'inject_preloads' ), 1 );
		}

		// Misc
		if ( ! empty( $options['disable_update_emails'] ) ) {
			add_filter( 'auto_core_update_send_email', '__return_false' );
			add_filter( 'auto_plugin_update_send_email', '__return_false' );
			add_filter( 'auto_theme_update_send_email', '__return_false' );
		}
		if ( ! empty( $options['disable_pingbacks'] ) ) {
			add_filter( 'pings_open', '__return_false', 9999, 2 );
		}
		if ( ! empty( $options['remove_recent_comments_css'] ) ) {
			add_filter( 'show_recent_comments_widget_style', '__return_false' );
		}
		if ( ! empty( $options['disable_login_language'] ) ) {
			add_filter( 'login_display_language_dropdown', '__return_false' );
		}
		if ( ! empty( $options['disable_capital_p_dangit'] ) ) {
			remove_filter( 'the_title', 'capital_P_dangit', 11 );
			remove_filter( 'the_content', 'capital_P_dangit', 11 );
			remove_filter( 'comment_text', 'capital_P_dangit', 31 );
		}
		if ( ! empty( $options['disable_feeds'] ) ) {
			add_action( 'init', array( $this, 'disable_feeds_actions' ) );
		}
		if ( ! empty( $options['disable_self_pingbacks'] ) ) {
			add_action( 'pre_ping', array( $this, 'disable_self_pingbacks_action' ) );
		}
		if ( isset( $options['post_revisions_limit'] ) && $options['post_revisions_limit'] !== '' ) {
			add_filter( 'wp_revisions_to_keep', array( $this, 'limit_post_revisions_action' ), 10, 2 );
		}
	}

	public function inject_preloads() {
		if ( is_admin() ) return;
		$css_url = NABOODATABASE_URL . 'includes/public/css/naboodatabase-public.css?ver=' . NABOODATABASE_VERSION;
		echo "<link rel='preload' href='" . esc_url( $css_url ) . "' as='style' onload=\"this.onload=null;this.rel='stylesheet'\">\n";
		echo "<noscript><link rel='stylesheet' href='" . esc_url( $css_url ) . "'></noscript>\n";
	}

	public function minify_html_start() {
		if ( is_admin() || is_feed() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( empty( $_GET['sitemap'] ) && empty( $_GET['xml_sitemap'] ) ) {
			ob_start( array( $this, 'minify_html_output' ) );
		}
	}

	public function minify_html_output( $html ) {
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return $html;
		}
		if ( stripos( $html, '</html>' ) === false ) {
			return $html;
		}
		$search = array( '/\>[ \t]+/s', '/[ \t]+\</s', '/[ \t]{2,}/s', '/<!--(?!\[if).*?-->/s' );
		$replace = array( '>', '<', ' ', '' );
		return preg_replace( $search, $replace, $html );
	}

	public function disable_feeds_actions() {
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	public function disable_self_pingbacks_action( &$links ) {
		$home = get_option( 'home' );
		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $l ] );
			}
		}
	}

	public function limit_post_revisions_action( $num, $post ) {
		$options = get_option( $this->option_name, array() );
		$limit   = isset( $options['post_revisions_limit'] ) ? $options['post_revisions_limit'] : '';
		if ( $limit === '0' ) {
			return 0;
		} elseif ( intval( $limit ) > 0 ) {
			return intval( $limit );
		}
		return $num;
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		
		require __DIR__ . '/performance/views/admin-page.php';
	}

	public function sanitize_options( $input ) {
		$sanitized = array();
		$checkboxes = array( 'disable_emojis', 'disable_embeds', 'disable_xmlrpc', 'disable_heartbeat', 'disable_author_pages', 'disable_attachment_pages', 'disable_update_emails', 'disable_pingbacks', 'disable_native_comments', 'disable_theme_assets', 'remove_query_strings', 'disable_block_css', 'clean_head', 'preload_assets', 'disable_feeds', 'disable_self_pingbacks', 'disable_global_styles', 'defer_js', 'minify_html', 'consolidate_assets', 'cf_enable_integration', 'cf_brotli', 'cf_early_hints', 'cf_auto_minify', 'cf_tiered_cache' );
		foreach ( $checkboxes as $cb ) {
			$sanitized[ $cb ] = isset( $input[ $cb ] ) ? 1 : 0;
		}
		$sanitized['post_revisions_limit'] = sanitize_text_field( $input['post_revisions_limit'] ?? '' );
		$sanitized['cf_account_id']        = sanitize_text_field( $input['cf_account_id'] ?? '' );
		$sanitized['cf_zone_id']           = sanitize_text_field( $input['cf_zone_id'] ?? '' );
		$sanitized['cf_api_token']         = sanitize_text_field( $input['cf_api_token'] ?? '' );
		$sanitized['cache_ttl']            = absint( $input['cache_ttl'] ?? 3600 );
		$sanitized['page_cache_ttl']       = absint( $input['page_cache_ttl'] ?? 3600 );
		return $sanitized;
	}
}
