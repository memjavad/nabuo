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
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		$options = get_option( $this->option_name, array() );
		?>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

		<div class="wrap naboo-admin-page naboo-performance-wrap" style="font-family: 'Inter', sans-serif;">
			
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(245, 158, 11, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
					<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">⚡</span>
					<?php esc_html_e( 'Performance Optimizer', 'naboodatabase' ); ?>
				</h1>
				<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Fine-tune your database performance, optimize asset delivery, and monitor system bottlenecks.', 'naboodatabase' ); ?></p>
			</div>

			<?php
			global $wpdb;
			$total_scales   = wp_count_posts('psych_scale')->publish ?? 0;
			$indexed_scales = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}naboo_search_index") ?? 0;
			
			$cf_status = 'Disabled';
			if ( class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
				$cf = new \ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration();
				if ( $cf->is_active() ) {
					$cf_status = 'Active (' . ( $cf->get_zone_name() ?: 'Unknown Zone' ) . ')';
				}
			}
			
			$cache_status      = wp_using_ext_object_cache() ? '<span style="color:green;font-weight:bold;">Active (RAM)</span>' : '<span style="color:#d63638;font-weight:bold;">Inactive</span>';
			$page_cache_status = defined('WP_CACHE') && WP_CACHE && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ? '<span style="color:green;font-weight:bold;">Active (Disk)</span>' : '<span style="color:#d63638;font-weight:bold;">Inactive</span>';
			?>

			<div class="naboo-performance-metrics-bar" style="background: white; padding: 32px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 40px; display: flex; gap: 40px; align-items: center; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
				<div class="metric-item" style="flex: 1;">
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<div style="display: flex; justify-content: space-between; align-items: center;">
							<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">RAM Cache</span>
							<?php echo $cache_status; ?>
						</div>
						<div style="display: flex; gap: 8px;">
							<?php if ( wp_using_ext_object_cache() ) : ?>
								<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-uninstall-cache" style="color:#dc2626; font-size:12px;"><?php _e( 'Remove', 'naboodatabase' ); ?></button>
							<?php else : ?>
								<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-install-cache" style="font-size:12px;"><?php _e( 'Install', 'naboodatabase' ); ?></button>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
				<div class="metric-item" style="flex: 1;">
					<div style="display: flex; flex-direction: column; gap: 12px;">
						<div style="display: flex; justify-content: space-between; align-items: center;">
							<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Page Cache</span>
							<?php echo $page_cache_status; ?>
						</div>
						<div style="display: flex; gap: 8px;">
							<?php if ( defined('WP_CACHE') && WP_CACHE && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) : ?>
								<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-uninstall-page-cache" style="color:#dc2626; font-size:12px;"><?php _e( 'Remove', 'naboodatabase' ); ?></button>
								<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-clear-page-cache" style="color:#047857; border-color: #10b981; font-size:12px;"><?php _e( 'Purge', 'naboodatabase' ); ?></button>
							<?php else : ?>
								<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-install-page-cache" style="font-size:12px;"><?php _e( 'Install', 'naboodatabase' ); ?></button>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
				<div class="metric-item" style="flex: 1;">
					<div style="display: flex; flex-direction: column; gap: 8px;">
						<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Indexing</span>
						<div style="font-size: 24px; font-weight: 800; color: #1e293b;">
							<?php echo number_format( (int)$indexed_scales ); ?> / <?php echo number_format( (int)$total_scales ); ?> 
						</div>
						<div style="height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
							<div style="width: <?php echo $total_scales > 0 ? round( ( $indexed_scales / $total_scales ) * 100 ) : 0; ?>%; height: 100%; background: #10b981;"></div>
						</div>
					</div>
				</div>
				<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
				<div class="metric-item" style="flex: 1;">
					<div style="display: flex; flex-direction: column; gap: 8px;">
						<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Cloudflare</span>
						<div style="display: flex; align-items: center; gap: 10px;">
							<?php if ( strpos( $cf_status, 'Active' ) !== false ) : ?>
								<span style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></span>
								<span style="color:#10b981; font-weight: 700; font-size: 18px;"><?php _e( 'Active', 'naboodatabase' ); ?></span>
							<?php else : ?>
								<span style="width: 12px; height: 12px; background: #94a3b8; border-radius: 50%;"></span>
								<span style="color:#64748b; font-weight: 700; font-size: 18px;"><?php _e( 'Inactive', 'naboodatabase' ); ?></span>
							<?php endif; ?>
						</div>
						<span style="font-size: 12px; color: #94a3b8;"><?php echo esc_html( $cf_status ); ?></span>
					</div>
				</div>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'naboodatabase_performance_group' ); ?>

				<div class="naboo-admin-grid">
					
					<!-- UI: BLOAT SECTION -->
					<div class="naboo-admin-card">
						<h2><?php _e( 'Disable WordPress Bloat', 'naboodatabase' ); ?></h2>
						<table class="form-table">
							<?php 
							$clean_options = array(
								'disable_emojis'           => __( 'Disable Emojis', 'naboodatabase' ),
								'disable_embeds'           => __( 'Disable Embeds', 'naboodatabase' ),
								'disable_xmlrpc'           => __( 'Disable XML-RPC', 'naboodatabase' ),
								'disable_heartbeat'        => __( 'Disable Heartbeat API', 'naboodatabase' ),
								'disable_author_pages'     => __( 'Disable Author Archives', 'naboodatabase' ),
								'disable_attachment_pages' => __( 'Disable Attachment Pages', 'naboodatabase' ),
								'disable_update_emails'    => __( 'Disable Auto-Update Emails', 'naboodatabase' ),
								'disable_pingbacks'        => __( 'Disable All Pingbacks', 'naboodatabase' ),
								'disable_native_comments'  => __( 'Disable Native Comments', 'naboodatabase' ),
								'disable_theme_assets'     => __( 'Disable Active Theme Assets', 'naboodatabase' ),
							);
							foreach ( $clean_options as $key => $label ) : 
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $label ); ?></th>
								<td>
									<label class="naboo-switch">
										<input type="checkbox" name="<?php echo $this->option_name; ?>[<?php echo $key; ?>]" value="1" <?php checked( isset( $options[ $key ] ) ? $options[ $key ] : 0 ); ?> />
										<span class="slider round"></span>
									</label>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>

					<!-- UI: ASSET SECTION -->
					<div class="naboo-admin-card">
						<h2><?php _e( 'Optimization & Assets', 'naboodatabase' ); ?></h2>
						<table class="form-table">
							<?php 
							$asset_options = array(
								'remove_query_strings'  => __( 'Remove Query Strings', 'naboodatabase' ),
								'disable_block_css'     => __( 'Remove Gutenberg CSS', 'naboodatabase' ),
								'consolidate_assets'    => __( 'Consolidate Naboo Assets', 'naboodatabase' ),
								'defer_js'              => __( 'Defer JavaScript', 'naboodatabase' ),
								'minify_html'           => __( 'Minify HTML Output', 'naboodatabase' ),
								'clean_head'            => __( 'Clean Head Garbage', 'naboodatabase' ),
								'preload_assets'        => __( 'Preload Stylesheets', 'naboodatabase' ),
								'disable_global_styles' => __( 'Disable Global Scripts', 'naboodatabase' ),
							);
							foreach ( $asset_options as $key => $label ) : 
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $label ); ?></th>
								<td>
									<label class="naboo-switch">
										<input type="checkbox" name="<?php echo $this->option_name; ?>[<?php echo $key; ?>]" value="1" <?php checked( isset( $options[ $key ] ) ? $options[ $key ] : 0 ); ?> />
										<span class="slider round"></span>
									</label>
									<?php if ( $key === 'consolidate_assets' ) : ?>
										<button type="button" class="button button-link" id="naboo-clear-cache" style="font-size:11px;"><?php _e( 'Clear Cache', 'naboodatabase' ); ?></button>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</div>

					<!-- UI: CLOUDFLARE SECTION -->
					<div class="naboo-admin-card">
						<h2><?php _e( 'Cloudflare Integration', 'naboodatabase' ); ?></h2>
						<div style="padding: 24px;">
							<div class="naboo-form-row">
								<label><input type="checkbox" name="<?php echo $this->option_name; ?>[cf_enable_integration]" value="1" <?php checked( isset( $options['cf_enable_integration'] ) ? $options['cf_enable_integration'] : 0 ); ?> /> Enable CF API Access</label>
							</div>
							<div class="naboo-form-row"><label>Account ID</label><input type="text" name="<?php echo $this->option_name; ?>[cf_account_id]" value="<?php echo esc_attr( $options['cf_account_id'] ?? '' ); ?>" /></div>
							<div class="naboo-form-row"><label>Zone ID</label><input type="text" name="<?php echo $this->option_name; ?>[cf_zone_id]" value="<?php echo esc_attr( $options['cf_zone_id'] ?? '' ); ?>" /></div>
							<div class="naboo-form-row"><label>API Token</label><input type="password" name="<?php echo $this->option_name; ?>[cf_api_token]" value="<?php echo esc_attr( $options['cf_api_token'] ?? '' ); ?>" /></div>
							
							<div style="display: flex; gap: 8px; margin-top: 20px;">
								<button type="button" id="naboo-whitelist-cf-ip" class="naboo-btn naboo-btn-secondary" style="font-size:12px;">Whitelist IP</button>
								<button type="button" id="naboo-purge-cf-all" class="naboo-btn naboo-btn-secondary" style="font-size:12px;">Purge All</button>
								<button type="button" id="naboo-deploy-cf-worker" class="naboo-btn" style="background:#f56e28; color:white; font-size:12px;">Deploy Worker</button>
							</div>
						</div>
					</div>

					<!-- UI: CACHING CONFIG SECTION -->
					<div class="naboo-admin-card">
						<h2><?php _e( 'Caching TTL & Security', 'naboodatabase' ); ?></h2>
						<table class="form-table">
							<tr>
								<th scope="row">Object Cache TTL (s)</th>
								<td><input type="number" name="<?php echo $this->option_name; ?>[cache_ttl]" value="<?php echo esc_attr( $options['cache_ttl'] ?? 3600 ); ?>" /></td>
							</tr>
							<tr>
								<th scope="row">Page Cache TTL (s)</th>
								<td><input type="number" name="<?php echo $this->option_name; ?>[page_cache_ttl]" value="<?php echo esc_attr( $options['page_cache_ttl'] ?? 3600 ); ?>" /></td>
							</tr>
							<tr>
								<th scope="row">Revisions Limit</th>
								<td>
									<select name="<?php echo $this->option_name; ?>[post_revisions_limit]">
										<option value="" <?php selected( $options['post_revisions_limit'] ?? '', '' ); ?>>Unlimited</option>
										<option value="0" <?php selected( $options['post_revisions_limit'] ?? '', '0' ); ?>>Disabled</option>
										<option value="5" <?php selected( $options['post_revisions_limit'] ?? '', '5' ); ?>>5 Versions</option>
									</select>
								</td>
							</tr>
						</table>
					</div>

				</div>

				<div class="naboo-save-bar">
					<button type="submit" class="naboo-btn naboo-btn-primary">Save Optimization Settings</button>
				</div>
			</form>
		</div>

		<style>
			.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; }
			.naboo-admin-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; }
			.naboo-admin-card h2 { margin: 0; padding: 20px 24px; font-size: 18px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; background: #f8fafc; }
			.form-table th { width: 260px !important; padding: 16px 24px !important; font-size: 13px !important; }
			.form-table td { padding: 12px 24px !important; }
			.naboo-form-row { margin-bottom: 12px; }
			.naboo-form-row label { display: block; font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 4px; }
			.naboo-form-row input { width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
			.naboo-save-bar { position: sticky; bottom: 20px; z-index: 100; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); padding: 20px 40px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; margin-top: 40px; }
			.naboo-btn { padding: 10px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
			.naboo-btn-primary { background: #4f46e5; color: white; }
			.naboo-btn-secondary { background: white; border-color: #e2e8f0; color: #475569; }
			.naboo-btn:hover { opacity: 0.9; transform: translateY(-1px); }
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			const nonce = '<?php echo wp_create_nonce( "naboo_performance_action" ); ?>';
			const cfNonce = '<?php echo wp_create_nonce( "naboo_cf_action" ); ?>';

			$('#naboo-clear-cache').click(function() {
				$.post(ajaxurl, { action: 'naboo_clear_asset_cache', nonce: '<?php echo wp_create_nonce( "naboo_clear_cache" ); ?>' }, function(r) { alert(r.data); });
			});
			$('#naboo-whitelist-cf-ip').click(function() {
				$.post(ajaxurl, { action: 'naboo_cf_whitelist_ip', _wpnonce: cfNonce }, function(r) { alert(r.data); });
			});
			$('#naboo-purge-cf-all').click(function() {
				$.post(ajaxurl, { action: 'naboo_purge_cloudflare_all', _wpnonce: cfNonce }, function(r) { alert(r.data); });
			});
			$('#naboo-deploy-cf-worker').click(function() {
				$.post(ajaxurl, { action: 'naboo_deploy_cloudflare_worker', _wpnonce: cfNonce }, function(r) { alert(r.data); });
			});
			$('#naboo-install-cache, #naboo-uninstall-cache').click(function() {
				const act = $(this).attr('id') === 'naboo-install-cache' ? 'naboo_install_object_cache' : 'naboo_uninstall_object_cache';
				$.post(ajaxurl, { action: act, nonce: nonce }, function(r) { alert(r.data); location.reload(); });
			});
			$('#naboo-install-page-cache, #naboo-uninstall-page-cache').click(function() {
				const act = $(this).attr('id') === 'naboo-install-page-cache' ? 'naboo_install_page_cache' : 'naboo_uninstall_page_cache';
				$.post(ajaxurl, { action: act, nonce: nonce }, function(r) { alert(r.data); location.reload(); });
			});
			$('#naboo-clear-page-cache').click(function() {
				$.post(ajaxurl, { action: 'naboo_clear_page_cache', nonce: nonce }, function(r) { alert(r.data); });
			});
		});
		</script>
		<?php
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
