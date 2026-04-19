<?php

namespace ArabPsychology\NabooDatabase;

use ArabPsychology\NabooDatabase\Core\CPT;
use ArabPsychology\NabooDatabase\Core\Widget;
use ArabPsychology\NabooDatabase\Core\Security;
use ArabPsychology\NabooDatabase\Core\Upload_Security;
use ArabPsychology\NabooDatabase\Core\Security_Logger;
use ArabPsychology\NabooDatabase\Core\Installer;
use ArabPsychology\NabooDatabase\Core\WAF;
use ArabPsychology\NabooDatabase\Admin\Admin;
use ArabPsychology\NabooDatabase\Admin\Dashboard;
use ArabPsychology\NabooDatabase\Admin\Theme_Customizer;
use ArabPsychology\NabooDatabase\Admin\Advanced_Admin_Dashboard;
use ArabPsychology\NabooDatabase\Admin\Submission_Management_Queue;
use ArabPsychology\NabooDatabase\Admin\Scale_Editing_Tools;
use ArabPsychology\NabooDatabase\Admin\Bulk_Import_Tool;
use ArabPsychology\NabooDatabase\Admin\Scale_Validation;
use ArabPsychology\NabooDatabase\Admin\Bulk_Operations;
use ArabPsychology\NabooDatabase\Admin\User_Role_Management;
use ArabPsychology\NabooDatabase\Admin\Email_Notifications_System;
use ArabPsychology\NabooDatabase\Admin\Contributor_Management;
use ArabPsychology\NabooDatabase\Admin\Admin_Reports_Generator;
use ArabPsychology\NabooDatabase\Admin\Export_Analytics_Reports;
use ArabPsychology\NabooDatabase\Admin\Performance_Metrics_Dashboard;
use ArabPsychology\NabooDatabase\Admin\Advanced_Caching_System;
use ArabPsychology\NabooDatabase\Admin\API_Rate_Limiting;
use ArabPsychology\NabooDatabase\Admin\Settings_Ajax;
use ArabPsychology\NabooDatabase\Admin\Settings_Center;
use ArabPsychology\NabooDatabase\Admin\Comments_Moderation;
use ArabPsychology\NabooDatabase\Admin\Ratings_Moderation;
use ArabPsychology\NabooDatabase\Admin\Batch_AI;
use ArabPsychology\NabooDatabase\Admin\Pending_Processor;
use ArabPsychology\NabooDatabase\Admin\SEO_Settings;
use ArabPsychology\NabooDatabase\Admin\Performance_Optimizer;
use ArabPsychology\NabooDatabase\Admin\Security_Center;
use ArabPsychology\NabooDatabase\Admin\Search_Admin;
use ArabPsychology\NabooDatabase\Admin\Glossary_Admin;
use ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration;
use ArabPsychology\NabooDatabase\Admin\Health_Optimizer;
use ArabPsychology\NabooDatabase\Admin\Diagnostics;
use ArabPsychology\NabooDatabase\Public\Frontend;
use ArabPsychology\NabooDatabase\Public\Ajax;
use ArabPsychology\NabooDatabase\Public\User_Dashboard;
use ArabPsychology\NabooDatabase\Public\API;
use ArabPsychology\NabooDatabase\Public\SEO_Manager;
use ArabPsychology\NabooDatabase\Public\Search_Guard;
use ArabPsychology\NabooDatabase\Public\Admin_Bar;
use ArabPsychology\NabooDatabase\Public\Content_Manager;
use ArabPsychology\NabooDatabase\Public\Glossary_Public;
use ArabPsychology\NabooDatabase\Public\Theme_Builder;
use ArabPsychology\NabooDatabase\Public\Favorites;
use ArabPsychology\NabooDatabase\Public\Ratings;
use ArabPsychology\NabooDatabase\Public\Related_Scales;
use ArabPsychology\NabooDatabase\Public\Comments;
use ArabPsychology\NabooDatabase\Public\Advanced_Search;
use ArabPsychology\NabooDatabase\Public\Smart_Search_Suggestions;
use ArabPsychology\NabooDatabase\Public\Search_Result_Improvements;
use ArabPsychology\NabooDatabase\Public\Scale_Index;
use ArabPsychology\NabooDatabase\Public\PDF_Export;
use ArabPsychology\NabooDatabase\Public\File_Download_Features;
use ArabPsychology\NabooDatabase\Public\Data_Export_Features;
use ArabPsychology\NabooDatabase\Public\Scale_Collections;
use ArabPsychology\NabooDatabase\Public\Scale_Comparison;
use ArabPsychology\NabooDatabase\Public\Scale_Popularity_Analytics;
use ArabPsychology\NabooDatabase\Public\User_Analytics_Dashboard;
use ArabPsychology\NabooDatabase\Public\Search_Analytics_Trends;
use ArabPsychology\NabooDatabase\Public\Scale_Recommendation_Engine;
use ArabPsychology\NabooDatabase\Core\AI_Extractor;
use ArabPsychology\NabooDatabase\Public\AI_Frontend;

class Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		if ( defined( 'NABOODATABASE_VERSION' ) ) {
			$this->version = NABOODATABASE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'naboodatabase';

		$this->load_dependencies();
		$this->define_security_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
        // Autoloader handles class loading.
        // We just need to instantiate the Loader.
		$this->loader = new Loader();
	}

	private function define_security_hooks() {
		$security = new Security();
		$upload_security = new Upload_Security();

		$this->loader->add_action( 'send_headers', $security, 'send_security_headers' );
		$this->loader->add_filter( 'xmlrpc_enabled', $security, 'disable_xmlrpc' );
		$this->loader->add_filter( 'xmlrpc_methods', $security, 'remove_xmlrpc_methods' );

		// WAF - Proactive Firewall
		$waf = new WAF();
		$this->loader->add_action( 'init', $waf, 'run_firewall', 1 );

		// Brute Force & Advanced Hardening
		$this->loader->add_filter( 'authenticate', $security, 'check_login_lockout', 1 );
		$this->loader->add_action( 'wp_login_failed', $security, 'log_failed_login' );
		$this->loader->add_action( 'init', $security, 'block_user_enumeration' );
		$this->loader->add_filter( 'rest_authentication_errors', $security, 'restrict_rest_api' );
		$this->loader->add_filter( 'http_headers_useragent', $security, 'spoof_http_user_agent' );
		$this->loader->add_filter( 'http_api_transports', $security, 'force_curl_transport' );
		$this->loader->add_action( 'http_api_curl', $security, 'force_local_loopback', 10, 3 );
		$this->loader->add_filter( 'site_status_tests', $security, 'override_site_health_tests' );
		$this->loader->add_action( 'init', $security, 'hide_wp_version' );

		// v3.0 Cyber Security
		$this->loader->add_action( 'init', $security, 'handle_login_renaming', 5 );
		$this->loader->add_filter( 'site_url', $security, 'filter_site_url', 10, 4 );
		$this->loader->add_filter( 'network_site_url', $security, 'filter_network_site_url', 10, 3 );
		$this->loader->add_filter( 'wp_redirect', $security, 'filter_wp_redirect', 10, 2 );
		$this->loader->add_action( 'admin_init', $security, 'apply_server_hardening' );

		// Security Logger & Table Creation
		$this->loader->add_action( 'init', 'ArabPsychology\\NabooDatabase\\Core\\Installer', 'maybe_create_tables' );

		// Search Index Table — created independently so bumping Installer::DB_VERSION
		// is not required and other feature tables are never disrupted.
		add_action( 'init', function() {
			if ( ! get_option( 'naboo_search_index_created' ) ) {
				\ArabPsychology\NabooDatabase\Admin\Database_Indexer::create_table();
				update_option( 'naboo_search_index_created', '1' );
			}
		}, 20 );
		
		$security_logger = new Security_Logger();
		// We'll pass this logger to other classes as needed.

		$this->loader->add_filter( 'wp_handle_upload_prefilter', $upload_security, 'validate_scale_upload' );
		$this->loader->add_action( 'admin_init', $upload_security, 'init_secure_upload_dir' );
	}

	private function define_admin_hooks() {
		$plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version() );
        $dashboard = new Dashboard();
        $theme_customizer = new Theme_Customizer( $this->get_plugin_name(), $this->get_version() );
        $advanced_dashboard = new Advanced_Admin_Dashboard( $this->get_plugin_name(), $this->get_version() );
        $submission_queue = new Submission_Management_Queue( $this->get_plugin_name(), $this->get_version() );
        $scale_editing_tools = new Scale_Editing_Tools( $this->get_plugin_name(), $this->get_version() );
        $bulk_import_tool = new Bulk_Import_Tool( $this->get_plugin_name(), $this->get_version() );
        $scale_validation = new Scale_Validation( $this->get_plugin_name(), $this->get_version() );
        $bulk_operations = new Bulk_Operations( $this->get_plugin_name(), $this->get_version() );
        $user_role_management = new User_Role_Management( $this->get_plugin_name(), $this->get_version() );
        $email_notifications = new Email_Notifications_System( $this->get_plugin_name(), $this->get_version() );
        $contributor_management = new Contributor_Management( $this->get_plugin_name(), $this->get_version() );
        $admin_reports_generator = new Admin_Reports_Generator( $this->get_plugin_name(), $this->get_version() );
        $export_analytics_reports = new Export_Analytics_Reports( $this->get_plugin_name(), $this->get_version() );
        $performance_metrics_dashboard = new Performance_Metrics_Dashboard( $this->get_plugin_name(), $this->get_version() );
        $advanced_caching_system = new Advanced_Caching_System( $this->get_plugin_name(), $this->get_version() );
        $api_rate_limiting = new API_Rate_Limiting( $this->get_plugin_name(), $this->get_version() );
        $settings_center = new Settings_Center( $this->get_plugin_name(), $this->get_version() );
        $settings_ajax = new Settings_Ajax();
        $comments_moderation = new Comments_Moderation( $this->get_plugin_name(), $this->get_version() );
        $ratings_moderation = new Ratings_Moderation( $this->get_plugin_name(), $this->get_version() );
        $seo_settings = new SEO_Settings( $this->get_plugin_name(), $this->get_version() );
        $performance_optimizer = new Performance_Optimizer( $this->get_plugin_name(), $this->get_version() );
        $security_center = new Security_Center( $this->get_plugin_name(), $this->get_version() );
        $glossary_admin = new Glossary_Admin( $this->get_plugin_name(), $this->get_version() );
        $health_optimizer = new Health_Optimizer( $this->get_plugin_name(), $this->get_version() );
        $cloudflare_integration = new Cloudflare_Integration();
        $cloudflare_integration->init_hooks();

		// Enqueue styles and scripts
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_enqueue_scripts', $theme_customizer, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_enqueue_scripts', $advanced_dashboard, 'enqueue_styles' );
        
		// Theme Customizer Menu (hooks still used for register_settings etc.)
        $this->loader->add_action( 'admin_menu', $theme_customizer, 'add_admin_menu', 20 );
        $this->loader->add_action( 'admin_init', $theme_customizer, 'register_settings' );

        // Settings Center (priority 20 so its add_submenu_page overrides the placeholder above)
        $this->loader->add_action( 'admin_menu', $settings_center, 'register_menu', 20 );
        $this->loader->add_action( 'admin_init', $settings_center, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $settings_center, 'enqueue_assets' );

        // Settings AJAX Handlers
        $settings_ajax->register_hooks();

        // SEO & Schema Settings
        $this->loader->add_action( 'admin_menu', $seo_settings, 'register_menu', 20 );
        $this->loader->add_action( 'admin_init', $seo_settings, 'register_settings' );
        $this->loader->add_action( 'wp_ajax_naboo_generate_sitemap', $seo_settings, 'ajax_generate_sitemap' );
        $this->loader->add_action( 'wp_ajax_nopriv_naboo_generate_sitemap', $seo_settings, 'ajax_generate_sitemap' );
		
		// Sitemap Automation & Dynamic Delivery
		$this->loader->add_action( 'init', $seo_settings, 'dynamic_sitemap_endpoint' );
		$this->loader->add_action( 'save_post_psych_scale', $seo_settings, 'trigger_sitemap_update', 10, 3 );
		$this->loader->add_action( 'edit_term', $seo_settings, 'trigger_sitemap_update_taxonomy', 10, 3 );
		$this->loader->add_action( 'delete_term', $seo_settings, 'trigger_sitemap_update_taxonomy', 10, 3 );

		// Regenerate sitemap when a psych_scale post is trashed or permanently deleted.
		$this->loader->add_action( 'trash_post', $seo_settings, 'trigger_sitemap_update_on_delete' );
		$this->loader->add_action( 'delete_post', $seo_settings, 'trigger_sitemap_update_on_delete' );

		// Inject sitemap URL into WordPress virtual robots.txt.
		$this->loader->add_filter( 'robots_txt', $seo_settings, 'inject_sitemap_in_robots', 10, 1 );

		// Weekly cron: keep sitemap fresh even without post activity.
		if ( ! wp_next_scheduled( 'naboo_weekly_sitemap_cron' ) ) {
			wp_schedule_event( time(), 'weekly', 'naboo_weekly_sitemap_cron' );
		}
		$this->loader->add_action( 'naboo_weekly_sitemap_cron', $seo_settings, 'cron_regenerate_sitemap' );

		// SEO: inject canonical URL + hreflang tags on psych_scale singular pages.
		$this->loader->add_action( 'wp_head', $seo_settings, 'inject_canonical_and_hreflang', 1 );

		// Advanced Search Indexer — registered directly (bypassing Loader) because the
		// Loader's signature is ($hook, $component, $callback, $priority, $accepted_args)
		// which cannot accept a callable array as $component without shifting arguments.
		add_action( 'save_post', [ 'ArabPsychology\\NabooDatabase\\Admin\\Database_Indexer', 'trigger_sync_on_save' ], 99, 3 );
		add_action( 'deleted_post', [ 'ArabPsychology\\NabooDatabase\\Admin\\Database_Indexer', 'trigger_sync_on_delete' ], 10, 1 );
		\ArabPsychology\NabooDatabase\Admin\Database_Indexer::init_async_hooks();

		// Admin list sortable columns for psych_scale.
		$this->loader->add_filter( 'manage_edit-psych_scale_sortable_columns', $plugin_admin, 'sortable_columns' );
		$this->loader->add_action( 'pre_get_posts', $plugin_admin, 'handle_custom_sort' );

		// Invalidate admin review bar transient when a psych_scale changes status.
		add_action( 'transition_post_status', function( $new, $old, $post ) {
			if ( $post->post_type === 'psych_scale' ) {
				delete_transient( 'naboo_admin_bar_next_0' );
				delete_transient( 'naboo_admin_bar_next_' . $post->ID );
			}
		}, 10, 3 );

        // Performance Optimizer
        $this->loader->add_action( 'admin_menu', $performance_optimizer, 'register_menu', 20 );
        $this->loader->add_action( 'admin_init', $performance_optimizer, 'register_settings' );
        $performance_optimizer->init_optimizations();

        // Security Center
        $this->loader->add_action( 'admin_menu', $security_center, 'register_menu', 20 );
        $this->loader->add_action( 'admin_init', $security_center, 'register_settings' );

        // Comments Moderation
        $this->loader->add_action( 'admin_menu', $comments_moderation, 'register_menu', 20 );
        $this->loader->add_action( 'admin_post_naboo_comment_action', $comments_moderation, 'handle_action' );

        // Ratings Moderation
        $this->loader->add_action( 'admin_menu', $ratings_moderation, 'register_menu', 20 );
        $this->loader->add_action( 'admin_post_naboo_rating_action', $ratings_moderation, 'handle_action' );
        
        // Batch AI Processing
        $batch_ai = new Batch_AI();
        $batch_ai->register();

        // Pending Processor
        $pending_processor = new Pending_Processor();
        $this->loader->add_action( 'admin_menu', $pending_processor, 'add_plugin_admin_menu', 20 );
        $this->loader->add_action( 'admin_enqueue_scripts', $pending_processor, 'enqueue_scripts' );
        $this->loader->add_action( 'wp_ajax_naboo_process_pending_scale', $pending_processor, 'ajax_process_pending_scale' );

        // Queue cleanup cron: reset stuck 'processing' items + purge old 'done' rows daily.
        if ( ! wp_next_scheduled( 'naboo_queue_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'naboo_queue_cleanup' );
        }
        add_action( 'naboo_queue_cleanup', [ 'ArabPsychology\\NabooDatabase\\Core\\Installer', 'reset_stuck_processing_items' ] );
        add_action( 'naboo_queue_cleanup', [ 'ArabPsychology\\NabooDatabase\\Core\\Installer', 'purge_old_done_items' ] );

        // Advanced Admin Dashboard Menu
        $this->loader->add_action( 'admin_menu', $advanced_dashboard, 'add_admin_menu' );
        $this->loader->add_action( 'admin_menu', $advanced_dashboard, 'reorder_submenus', 999 );

        // Search Engine Admin Page
        $search_admin = new Search_Admin( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_menu', $search_admin, 'register_menu', 20 );

        // Submission Management Queue
        $this->loader->add_action( 'rest_api_init', $submission_queue, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $submission_queue, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $submission_queue, 'enqueue_admin_scripts' );
        
        
        // Scale Editing Tools
        $this->loader->add_action( 'rest_api_init', $scale_editing_tools, 'register_endpoints' );
        
        // Bulk Import Tool
        $this->loader->add_action( 'rest_api_init', $bulk_import_tool, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $bulk_import_tool, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $bulk_import_tool, 'enqueue_admin_scripts' );
        
        // Scale Validation
        $this->loader->add_action( 'rest_api_init', $scale_validation, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $scale_validation, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $scale_validation, 'enqueue_admin_scripts' );
        
        // Bulk Operations
        $this->loader->add_action( 'rest_api_init', $bulk_operations, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $bulk_operations, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $bulk_operations, 'enqueue_admin_scripts' );
        
        // User Role Management
        $this->loader->add_action( 'rest_api_init', $user_role_management, 'register_endpoints' );
        
        // Email Notifications System
        $this->loader->add_action( 'rest_api_init', $email_notifications, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $email_notifications, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $email_notifications, 'enqueue_admin_scripts' );
        
        // Contributor Management
        $this->loader->add_action( 'rest_api_init', $contributor_management, 'register_endpoints' );
        
        // Admin Reports Generator
        $this->loader->add_action( 'rest_api_init', $admin_reports_generator, 'register_endpoints' );
        
        // Export Analytics Reports
        $this->loader->add_action( 'rest_api_init', $export_analytics_reports, 'register_endpoints' );
        
        // Performance Metrics Dashboard
        $this->loader->add_action( 'rest_api_init', $performance_metrics_dashboard, 'register_endpoints' );
        
        // Advanced Caching System
        $this->loader->add_action( 'rest_api_init', $advanced_caching_system, 'register_endpoints' );
        
        // API Rate Limiting
        $this->loader->add_action( 'rest_api_init', $api_rate_limiting, 'register_endpoints' );
        $this->loader->add_action( 'admin_menu', $api_rate_limiting, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $api_rate_limiting, 'enqueue_admin_scripts' );
        
        // Add meta boxes
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_admin, 'save_meta_box_data' );

        // Custom Columns
        $this->loader->add_filter( 'manage_psych_scale_posts_columns', $plugin_admin, 'manage_columns' );
        $this->loader->add_action( 'manage_psych_scale_posts_custom_column', $plugin_admin, 'manage_custom_column', 10, 2 );

        // Glossary Admin
        $this->loader->add_action( 'admin_menu', $glossary_admin, 'add_admin_menu' );
        $this->loader->add_action( 'add_meta_boxes', $glossary_admin, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $glossary_admin, 'save_meta_box_data' );
        $this->loader->add_filter( 'manage_naboo_glossary_posts_columns', $glossary_admin, 'manage_columns' );
        $this->loader->add_action( 'manage_naboo_glossary_posts_custom_column', $glossary_admin, 'manage_custom_column', 10, 2 );

        // Health & Maintenance
        $this->loader->add_action( 'admin_menu', $health_optimizer, 'register_menu', 20 );
        $health_optimizer->register_ajax();

        // Diagnostics Dashboard
        $diagnostics = new Diagnostics( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_menu', $diagnostics, 'register_menu', 25 );
	}

	private function define_public_hooks() {
		$plugin_public = new Frontend( $this->get_plugin_name(), $this->get_version() );
        $ajax = new Ajax();
        $user_dashboard = new User_Dashboard();
        $api = new API();
        $favorites = new Favorites( $this->get_plugin_name(), $this->get_version() );
        $ratings = new Ratings( $this->get_plugin_name(), $this->get_version() );
        $related_scales = new Related_Scales( $this->get_plugin_name(), $this->get_version() );
        $comments = new Comments( $this->get_plugin_name(), $this->get_version() );
        $advanced_search = new Advanced_Search( $this->get_plugin_name(), $this->get_version() );
        $smart_search_suggestions = new Smart_Search_Suggestions( $this->get_plugin_name(), $this->get_version() );
        $search_result_improvements = new Search_Result_Improvements( $this->get_plugin_name(), $this->get_version() );
        $pdf_export = new PDF_Export( $this->get_plugin_name(), $this->get_version() );
        $file_download_features = new File_Download_Features( $this->get_plugin_name(), $this->get_version() );
        $data_export_features = new Data_Export_Features( $this->get_plugin_name(), $this->get_version() );
        $scale_collections = new Scale_Collections( $this->get_plugin_name(), $this->get_version() );
        $scale_comparison = new Scale_Comparison( $this->get_plugin_name(), $this->get_version() );
        $scale_popularity_analytics = new Scale_Popularity_Analytics( $this->get_plugin_name(), $this->get_version() );
        $user_analytics_dashboard = new User_Analytics_Dashboard( $this->get_plugin_name(), $this->get_version() );
        $search_analytics_trends = new Search_Analytics_Trends( $this->get_plugin_name(), $this->get_version() );
        $scale_recommendation_engine = new Scale_Recommendation_Engine( $this->get_plugin_name(), $this->get_version() );
        $ai_frontend = new AI_Frontend( $this->get_plugin_name(), $this->get_version() );
        $glossary_public = new Glossary_Public( $this->get_plugin_name(), $this->get_version() );

        // New Modular Frontend Managers
        $seo_manager     = new SEO_Manager();
        $search_guard    = new Search_Guard();
        $admin_bar       = new Admin_Bar();
        $content_manager = new Content_Manager();
        
        // Favorites System
        $this->loader->add_action( 'init', $favorites, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $favorites, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $favorites, 'enqueue_scripts' );
        $this->loader->add_action( 'naboo_after_user_dashboard', $favorites, 'add_favorites_dashboard_section' );
        
        // Ratings System
        $this->loader->add_action( 'init', $ratings, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $ratings, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $ratings, 'enqueue_scripts' );
        $this->loader->add_filter( 'the_content', $ratings, 'inject_rating_section', 20 );
        $this->loader->add_action( 'save_post_psych_scale', $ratings, 'add_default_rating_on_publish', 10, 3 );
        
        // Related Scales System
        $this->loader->add_action( 'wp_enqueue_scripts', $related_scales, 'enqueue_scripts' );
        $this->loader->add_filter( 'the_content', $related_scales, 'inject_related_scales', 15 );
        $this->loader->add_action( 'wp_ajax_naboo_get_related_scales', $related_scales, 'ajax_get_related_scales' );
        $this->loader->add_action( 'wp_ajax_nopriv_naboo_get_related_scales', $related_scales, 'ajax_get_related_scales' );
        
        // Comments System
        $this->loader->add_action( 'init', $comments, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $comments, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $comments, 'enqueue_scripts' );
        $this->loader->add_filter( 'the_content', $comments, 'inject_comments_section', 25 );
        
        // Advanced Search System
        $this->loader->add_action( 'rest_api_init', $advanced_search, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $advanced_search, 'enqueue_scripts' );
        
        // Smart Search Suggestions System
        $this->loader->add_action( 'init', $smart_search_suggestions, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $smart_search_suggestions, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $smart_search_suggestions, 'enqueue_scripts' );
        
        // Search Result Improvements System
        $this->loader->add_action( 'init', $search_result_improvements, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $search_result_improvements, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $search_result_improvements, 'enqueue_scripts' );
        
        // PDF Export System
        $this->loader->add_action( 'rest_api_init', $pdf_export, 'register_endpoints' );
        // $this->loader->add_action( 'wp_enqueue_scripts', $pdf_export, 'enqueue_scripts' );
        // $this->loader->add_filter( 'the_content', $pdf_export, 'inject_export_button', 18 );
        
        // File Download Features System
        $this->loader->add_action( 'init', $file_download_features, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $file_download_features, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $file_download_features, 'enqueue_scripts' );
        
        // Data Export Features System
        $this->loader->add_action( 'rest_api_init', $data_export_features, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $data_export_features, 'enqueue_scripts' );
        
        // Scale Collections System
        $this->loader->add_action( 'init', $scale_collections, 'create_table' );
        $this->loader->add_action( 'rest_api_init', $scale_collections, 'register_endpoints' );
        // $this->loader->add_action( 'wp_enqueue_scripts', $scale_collections, 'enqueue_scripts' );
        // $this->loader->add_filter( 'the_content', $scale_collections, 'inject_add_to_collection_button', 18 );
        
        // Scale Comparison System
        $this->loader->add_action( 'rest_api_init', $scale_comparison, 'register_endpoints' );
        // $this->loader->add_action( 'wp_enqueue_scripts', $scale_comparison, 'enqueue_scripts' );
        // $this->loader->add_filter( 'the_content', $scale_comparison, 'inject_add_to_compare_button', 19 );
        // $this->loader->add_action( 'wp_footer', $scale_comparison, 'render_comparison_bar' );
        
        // Scale Popularity Analytics System
        $this->loader->add_action( 'rest_api_init', $scale_popularity_analytics, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $scale_popularity_analytics, 'enqueue_scripts' );
        
        // User Analytics Dashboard System
        $this->loader->add_action( 'rest_api_init', $user_analytics_dashboard, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $user_analytics_dashboard, 'enqueue_scripts' );
        $this->loader->add_filter( 'naboo_after_dashboard_content', $user_analytics_dashboard, 'inject_dashboard_tab' );
        
        // Search Analytics & Trends System
        $this->loader->add_action( 'rest_api_init', $search_analytics_trends, 'register_endpoints' );
        
        // Scale Recommendation Engine System
        $this->loader->add_action( 'rest_api_init', $scale_recommendation_engine, 'register_endpoints' );
        $this->loader->add_action( 'wp_enqueue_scripts', $scale_recommendation_engine, 'enqueue_scripts' );
        $this->loader->add_filter( 'the_content', $scale_recommendation_engine, 'inject_recommendations_section', 30 );
        
        // Register API Routes
        $this->loader->add_action( 'rest_api_init', $api, 'register_routes' );
        
        // Theme Builder
        $theme_builder = new Theme_Builder();
        $theme_builder->init();

        // Register CPT
        $cpt = new CPT();
        $this->loader->add_action( 'init', $cpt, 'register' );
        
        // Register Widget
        $this->loader->add_action( 'widgets_init', $this, 'register_widgets' );

        // Enqueue styles and scripts
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // SEO & Site Icon Hooks
        $this->loader->add_action( 'wp_head', $seo_manager, 'add_schema_markup' );
        $this->loader->add_action( 'wp_head', $seo_manager, 'add_meta_description', 1 );
        $this->loader->add_action( 'wp_head', $seo_manager, 'add_opengraph_tags' );
        $this->loader->add_action( 'wp_head', $seo_manager, 'add_academic_meta_tags' );
        $this->loader->add_action( 'wp_head', $seo_manager, 'add_site_icons' );
        
        // Single Scale Features
        $this->loader->add_action( 'wp_head', $content_manager, 'track_views' );
        $this->loader->add_filter( 'the_content', $content_manager, 'inject_scale_content' );
        $this->loader->add_action( 'wp_footer', $admin_bar, 'render_admin_review_bar' );

        // Shortcodes
        $this->loader->add_shortcode( 'naboo_search', $plugin_public, 'render_search_shortcode' );
        $this->loader->add_shortcode( 'naboo_submit', $plugin_public, 'render_submission_shortcode' );
        $this->loader->add_shortcode( 'naboo_dashboard', $user_dashboard, 'render_dashboard_shortcode' );
        $this->loader->add_shortcode( 'naboo_ai_submit', $ai_frontend, 'render_ai_submit_shortcode' );

        // AJAX for AI Extraction
        $this->loader->add_action( 'wp_ajax_naboo_process_pdf_extraction', $ai_frontend, 'ajax_process_pdf_extraction' );
        $this->loader->add_action( 'wp_ajax_nopriv_naboo_process_pdf_extraction', $ai_frontend, 'ajax_process_pdf_extraction' );

        // AJAX for AI Final Submission
        $this->loader->add_action( 'wp_ajax_naboo_submit_ai_scale', $ai_frontend, 'ajax_submit_ai_scale' );
        $this->loader->add_action( 'wp_ajax_nopriv_naboo_submit_ai_scale', $ai_frontend, 'ajax_submit_ai_scale' );


        // AJAX for AI Single Field Refinement
        $this->loader->add_action( 'wp_ajax_naboo_refine_single_field', $ai_frontend, 'ajax_refine_single_field' );
        $this->loader->add_action( 'wp_ajax_nopriv_naboo_refine_single_field', $ai_frontend, 'ajax_refine_single_field' );

        // AJAX for Pending Scale Processor
        // Since $pending_processor is not globally available in this method block (it's in define_admin_hooks), 
        // we map it properly inside define_admin_hooks where it's instantiated.

        // AJAX for Inline AI Refinement (Admin Only)
        $this->loader->add_action( 'wp_ajax_naboo_inline_ai_refine', $ai_frontend, 'ajax_inline_ai_refine' );

        // AJAX for Inline Manual Edit (Admin Only)
        $this->loader->add_action( 'wp_ajax_naboo_get_raw_field_value', $admin_bar, 'ajax_get_raw_field_value' );
        $this->loader->add_action( 'wp_ajax_naboo_inline_manual_edit', $admin_bar, 'ajax_inline_manual_edit' );

        // Hide page title on pages that contain any NABOO shortcode.
        // Priority 10, 2 args — ID is passed since WP 4.4 so we can check the correct post.
        $this->loader->add_filter( 'the_title', $content_manager, 'hide_title_on_shortcode_pages', 10, 2 );

        // Disable WordPress default search.
        $this->loader->add_filter( 'get_search_form',   $search_guard, 'disable_search_form'    );
        $this->loader->add_action( 'template_redirect', $search_guard, 'redirect_search_to_apa' );
        $this->loader->add_action( 'pre_get_posts',     $search_guard, 'disable_search_query'   );

        // Glossary Shortcode, Assets & REST API
        $this->loader->add_shortcode( 'naboo_glossary', $glossary_public, 'render_shortcode' );
        $this->loader->add_action( 'wp_enqueue_scripts', $glossary_public, 'enqueue_assets' );
        $this->loader->add_action( 'rest_api_init', $glossary_public, 'register_rest_routes' );

        // Scale Index — virtual full-page browser
        $scale_index = new Scale_Index( $this->plugin_name, $this->version );
        $this->loader->add_action( 'init',              $scale_index, 'register_rewrite_rule' );
        $this->loader->add_action( 'rest_api_init',     $glossary_public, 'register_rest_routes' );
        $this->loader->add_filter( 'query_vars',        $scale_index, 'register_query_var' );
        $this->loader->add_action( 'template_redirect', $scale_index, 'maybe_render_index' );
	}

    public function register_widgets() {
        register_widget( 'ArabPsychology\NabooDatabase\Core\Widget' );
    }

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}

}
