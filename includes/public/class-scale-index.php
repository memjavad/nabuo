<?php
/**
 * Scale Index — Virtual full-page browser for psych_scale (or any CPT).
 *
 * Registers a URL slug (default: /scales-index/) that renders a complete
 * standalone HTML page using the same Glossary REST API and UI.
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Scale_Index class
 */
class Scale_Index {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/* ── Hooks ───────────────────────────────────────────────────── */

	/**
	 * Register the custom rewrite rule for the index page.
	 * Called on `init`.
	 */
	public function register_rewrite_rule() {
		if ( ! get_option( 'naboo_scale_index_enabled', 1 ) ) {
			return;
		}
		$slug = sanitize_title( get_option( 'naboo_scale_index_slug', 'scales-index' ) );
		add_rewrite_rule( '^' . $slug . '/?$', 'index.php?naboo_scale_index=1', 'top' );
	}

	/**
	 * Register custom query var so WP doesn't strip it.
	 */
	public function register_query_var( $vars ) {
		$vars[] = 'naboo_scale_index';
		return $vars;
	}

	/**
	 * When the index URL is requested, render our full page and exit.
	 * Called on `template_redirect`.
	 */
	public function maybe_render_index() {
		if ( ! get_query_var( 'naboo_scale_index' ) ) {
			return;
		}

		if ( ! get_option( 'naboo_scale_index_enabled', 1 ) ) {
			return;
		}

		// Enqueue our glossary assets
		$this->render_index_page();
		exit;
	}

	/* ── Rendering ───────────────────────────────────────────────── */

	private function render_index_page() {
		// Gather settings
		$post_type  = sanitize_text_field( get_option( 'naboo_scale_index_post_type', 'psych_scale' ) );
		$layout     = sanitize_text_field( get_option( 'naboo_glossary_layout', 'grid' ) );
		$meta_label = sanitize_text_field( get_option( 'naboo_scale_index_meta_label', '' ) );
		$per_page   = absint( get_option( 'naboo_glossary_per_page', 50 ) );
		$pagination = sanitize_text_field( get_option( 'naboo_glossary_pagination', 'infinite' ) );
		$accent     = sanitize_hex_color( get_option( 'naboo_glossary_accent_color', '#5b5ef4' ) ) ?: '#5b5ef4';
		$radius     = absint( get_option( 'naboo_glossary_card_radius', 12 ) );
		$site_name  = get_bloginfo( 'name' );
		$page_title = sanitize_text_field( get_option( 'naboo_scale_index_title', __( 'Scale Index', 'naboodatabase' ) ) );

		// Default meta label for psych_scale
		if ( empty( $meta_label ) && 'psych_scale' === $post_type ) {
			$meta_label = __( 'Author', 'naboodatabase' );
		}

		$css_url = plugin_dir_url( __FILE__ ) . 'css/glossary.css?v=' . $this->version;
		$js_url  = plugin_dir_url( __FILE__ ) . 'js/glossary.js?v=' . $this->version;
		$rest    = esc_url_raw( rest_url( 'naboo/v1/glossary' ) );
		$nonce   = wp_create_nonce( 'wp_rest' );

		$alphabet = array_merge( range( 'A', 'Z' ), array( '#' ) );

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $page_title . ' — ' . $site_name ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>">
<style>
/* ── Page chrome reset ─────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; background: #f5f6ff; font-family: 'Inter', sans-serif; }

/* ── Top bar ───────────────────────────────────────────────── */
.ngi-topbar {
	background: #fff;
	border-bottom: 2px solid #e2e4f8;
	padding: 0 clamp(20px, 5vw, 60px);
	height: 58px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	position: sticky;
	top: 0;
	z-index: 100;
	box-shadow: 0 1px 8px rgba(91,94,244,.07);
}

.ngi-topbar-brand {
	display: flex;
	align-items: center;
	gap: 10px;
	text-decoration: none;
	color: #111827;
	font-weight: 800;
	font-size: 1rem;
}

.ngi-topbar-brand img { height: 28px; border-radius: 6px; }

.ngi-topbar-back {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 0.855rem;
	font-weight: 700;
	color: <?php echo esc_js( $accent ); ?>;
	text-decoration: none;
	padding: 7px 16px;
	border-radius: 50px;
	border: 2px solid <?php echo esc_js( $accent ); ?>;
	transition: all .2s;
}
.ngi-topbar-back:hover {
	background: <?php echo esc_js( $accent ); ?>;
	color: #fff;
}

/* ── Heading section ──────────────────────────────────────── */
.ngi-hero {
	text-align: center;
	padding: 44px 20px 20px;
	max-width: 700px;
	margin: 0 auto;
}
.ngi-hero-title {
	font-size: clamp(1.6rem, 4vw, 2.4rem);
	font-weight: 800;
	color: #111827;
	letter-spacing: -0.02em;
	line-height: 1.2;
}
.ngi-hero-sub {
	font-size: 1rem;
	color: #6b7280;
	margin-top: 10px;
	font-weight: 500;
}

/* ── Make grabber fill the page width ─────────────────────── */
.ngi-wrapper {
	max-width: 100%;
	overflow-x: hidden;
}

/* Override glossary app to be full page here */
.ngi-wrapper .naboo-glossary-app {
	margin-left: 0 !important;
	margin-right: 0 !important;
	width: 100% !important;
	overflow: visible;
	padding: 36px clamp(20px, 5vw, 60px) 80px;
}

/* Ngg inner centering — max 1400px */
.ngi-wrapper .ngg-controls,
.ngi-wrapper .ngg-alpha-nav,
.ngi-wrapper .ngg-items-wrap,
.ngi-wrapper .ngg-pagination {
	max-width: 1400px;
	margin-left: auto;
	margin-right: auto;
}
</style>
</head>
<body>

<!-- Top Bar -->
<header class="ngi-topbar">
	<a class="ngi-topbar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php
		$logo = get_option( 'naboo_custom_logo_url', '' );
		if ( $logo ) {
			echo '<img src="' . esc_url( $logo ) . '" alt="Logo">';
		}
		?>
		<?php echo esc_html( $site_name ); ?>
	</a>
</header>

<!-- Hero heading -->
<div class="ngi-hero">
	<h1 class="ngi-hero-title"><?php echo esc_html( $page_title ); ?></h1>
	<p class="ngi-hero-sub">
		<?php
		$subtitle = get_option( 'naboo_scale_index_subtitle', '' );
		echo $subtitle
			? esc_html( $subtitle )
			: esc_html__( 'Alphabetical index of all available psychological scales and measures.', 'naboodatabase' );
		?>
	</p>
</div>

<!-- Glossary Shell — re-uses same JS/CSS as shortcode -->
<div class="ngi-wrapper">
<div id="naboo-glossary-app"
     class="naboo-glossary-app layout-<?php echo esc_attr( $layout ); ?>"
     data-post-type="<?php echo esc_attr( $post_type ); ?>"
     data-meta-key=""
     data-meta-label="<?php echo esc_attr( $meta_label ); ?>"
     data-per-page="<?php echo absint( $per_page ); ?>">

	<!-- Controls -->
	<div class="ngg-controls">
		<div class="ngg-search-wrap">
			<svg class="ngg-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
			<input type="search" id="ngg-search" class="ngg-search-input"
			       placeholder="<?php esc_attr_e( 'Search scales...', 'naboodatabase' ); ?>"
			       autocomplete="off" />
			<button class="ngg-search-clear" id="ngg-search-clear" aria-label="Clear">✕</button>
		</div>
		<div class="ngg-stats" id="ngg-stats"><span id="ngg-count"></span></div>
	</div>

	<!-- Alphabet -->
	<div class="ngg-alpha-nav" id="ngg-alpha-nav">
		<button class="ngg-alpha-btn active" data-letter="all"><?php esc_html_e( 'All', 'naboodatabase' ); ?></button>
		<?php foreach ( $alphabet as $letter ) : ?>
			<button class="ngg-alpha-btn" data-letter="<?php echo esc_attr( $letter ); ?>"><?php echo esc_html( $letter ); ?></button>
		<?php endforeach; ?>
	</div>

	<!-- Items -->
	<div class="ngg-items-wrap">
		<div class="ngg-items" id="ngg-items">
			<?php for ( $i = 0; $i < 8; $i++ ) : ?>
				<div class="ngg-skeleton">
					<div class="ngg-skeleton-title"></div>
					<div class="ngg-skeleton-sub"></div>
					<div class="ngg-skeleton-text"></div>
					<div class="ngg-skeleton-text short"></div>
				</div>
			<?php endfor; ?>
		</div>
		<div class="ngg-loader" id="ngg-loader" style="display:none;">
			<div class="ngg-spinner"></div>
			<span><?php esc_html_e( 'Loading...', 'naboodatabase' ); ?></span>
		</div>
		<div class="ngg-empty" id="ngg-empty" style="display:none;">
			<div class="ngg-empty-icon">🔍</div>
			<p><?php esc_html_e( 'No results found.', 'naboodatabase' ); ?></p>
		</div>
	</div>

	<!-- Pagination -->
	<div class="ngg-pagination" id="ngg-pagination" style="display:none;">
		<button class="ngg-page-btn" id="ngg-prev" disabled>← <?php esc_html_e( 'Prev', 'naboodatabase' ); ?></button>
		<span class="ngg-page-info" id="ngg-page-info"></span>
		<button class="ngg-page-btn" id="ngg-next"><?php esc_html_e( 'Next', 'naboodatabase' ); ?> →</button>
	</div>

	<div id="ngg-sentinel" style="height:10px;"></div>
</div>
</div>

<script>
window.nabooGlossaryConfig = {
	restUrl:         <?php echo wp_json_encode( $rest ); ?>,
	nonce:           <?php echo wp_json_encode( $nonce ); ?>,
	perPage:         <?php echo absint( $per_page ); ?>,
	pagination:      <?php echo wp_json_encode( $pagination ); ?>,
	showExcerpt:     <?php echo get_option( 'naboo_glossary_show_excerpt', 1 ) ? 'true' : 'false'; ?>,
	showSecondary:   <?php echo get_option( 'naboo_glossary_show_secondary', 1 ) ? 'true' : 'false'; ?>,
	showLetterIndex: <?php echo get_option( 'naboo_glossary_show_letter_index', 1 ) ? 'true' : 'false'; ?>,
	accentColor:     <?php echo wp_json_encode( $accent ); ?>,
	cardRadius:      <?php echo absint( $radius ); ?>,
	i18n: {
		search:      <?php echo wp_json_encode( __( 'Search scales...', 'naboodatabase' ) ); ?>,
		all:         <?php echo wp_json_encode( __( 'All', 'naboodatabase' ) ); ?>,
		noResults:   <?php echo wp_json_encode( __( 'No results found.', 'naboodatabase' ) ); ?>,
		loading:     <?php echo wp_json_encode( __( 'Loading...', 'naboodatabase' ) ); ?>,
		viewDetails: <?php echo wp_json_encode( __( 'View Details', 'naboodatabase' ) ); ?>,
		page:        <?php echo wp_json_encode( __( 'Page', 'naboodatabase' ) ); ?>,
		of:          <?php echo wp_json_encode( __( 'of', 'naboodatabase' ) ); ?>,
		prev:        '← <?php esc_html_e( 'Prev', 'naboodatabase' ); ?>',
		next:        '<?php esc_html_e( 'Next', 'naboodatabase' ); ?> →',
		items:       <?php echo wp_json_encode( __( 'scales', 'naboodatabase' ) ); ?>
	}
};
</script>
<script src="<?php echo esc_url( $js_url ); ?>"></script>

</body>
</html>
		<?php
	}

	/**
	 * Flush rewrite rules on activation.
	 * Called as static from the main plugin file.
	 */
	public static function flush_on_activation() {
		// Will be flushed by the Installer on activation.
		delete_option( 'naboo_scale_index_rewrite_flushed' );
	}
}
