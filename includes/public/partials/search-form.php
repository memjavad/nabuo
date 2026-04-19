<?php
/**
 * Scopus-Style Academic Search Form
 * Two-screen slide UI — Screen 1: clean white Scopus look
 *
 * @package ArabPsychology\NabooDatabase
 */

defined( 'ABSPATH' ) || exit;

$current_year = (int) gmdate( 'Y' );
$total_scales = wp_count_posts( 'psych_scale' );
$published    = isset( $total_scales->publish ) ? (int) $total_scales->publish : 0;
$num_cats     = wp_count_terms( array( 'taxonomy' => 'scale_category', 'hide_empty' => true ) );
if ( is_wp_error( $num_cats ) ) $num_cats = 0;

$naboo_options = get_option( 'naboodatabase_customizer_options', array() );
$main_search_logo = isset( $naboo_options['main_search_logo_url'] ) && ! empty( $naboo_options['main_search_logo_url'] ) ? $naboo_options['main_search_logo_url'] : ( isset( $naboo_options['logo_url'] ) ? $naboo_options['logo_url'] : '' );
$hide_main_search_logo = ! empty( $naboo_options['hide_main_search_logo'] );
$main_search_logo_width = isset( $naboo_options['main_search_logo_width'] ) ? intval( $naboo_options['main_search_logo_width'] ) : 68;
$main_search_title = isset( $naboo_options['main_search_title'] ) && $naboo_options['main_search_title'] !== '' ? $naboo_options['main_search_title'] : __( 'Naboo Psychological Scales Database', 'naboodatabase' );
?>

<div class="naboo-slide-wrapper" id="naboo-slide-wrapper">
<div class="naboo-slide-track" id="naboo-slide-track">

	<!-- ══════════════ SCREEN 1 : SEARCH ══════════════ -->
	<section class="naboo-screen naboo-screen-search" id="naboo-screen-search-main">
		<div class="naboo-search-container naboo-google-style-container">

			<!-- Center Logo -->
			<div class="naboo-sc-logo-center">
				<?php if ( ! $hide_main_search_logo ) : ?>
					<?php if ( ! empty( $main_search_logo ) ) : ?>
						<img src="<?php echo esc_url( $main_search_logo ); ?>" 
							 alt="<?php esc_attr_e( 'Naboo Psychological Database Logo', 'naboodatabase' ); ?>" 
							 class="naboo-sc-psi-logo" 
							 loading="lazy" 
							 style="filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.06)); margin-bottom: 8px; max-width: <?php echo esc_attr( $main_search_logo_width ); ?>px; height: auto;" />
					<?php else : ?>
						<svg width="<?php echo esc_attr( $main_search_logo_width ); ?>" height="<?php echo esc_attr( $main_search_logo_width ); ?>" viewBox="0 0 24 24" fill="none" class="naboo-sc-psi-logo" style="filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.06)); margin-bottom: 8px;">
							<defs>
								<linearGradient id="psiGradient" x1="0" y1="0" x2="24" y2="24" gradientUnits="userSpaceOnUse">
									<stop offset="0%" stop-color="#1e40af" />
									<stop offset="100%" stop-color="#3b82f6" />
								</linearGradient>
							</defs>
							
							<!-- Layer 1: Glowing soft mind orbit -->
							<circle cx="12" cy="12" r="10" fill="url(#psiGradient)" opacity="0.08" stroke="none"></circle>
							<!-- Layer 2: Network / connection dots representing the database -->
							<circle cx="12" cy="12" r="10" stroke="#1e40af" stroke-width="0.75" stroke-dasharray="1 3" stroke-linecap="round" fill="none" opacity="0.3"></circle>

							<!-- Layer 3: Refined modern Psi symbol Ψ -->
							<path d="M12 21V4 M5.5 8C5.5 15.5 18.5 15.5 18.5 8" stroke="url(#psiGradient)" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
						</svg>
					<?php endif; ?>
				<?php endif; ?>
				<h1 class="naboo-sc-hidden-h1"><?php echo esc_html( $main_search_title ); ?></h1>
			</div>

			<!-- Search rows -->
			<div class="naboo-sc-rows" id="naboo-search-rows">

				<!-- Main Google-style Search Bar (Row 1) -->
				<div class="naboo-sc-row naboo-sc-row-first naboo-google-search-bar" id="naboo-row-1" data-row="1">
					<!-- Hide the "search within" dropdown for the main bar to keep it ultra-clean -->
					<div class="naboo-sc-field-wrap naboo-hidden-field" style="display:none;">
						<select id="naboo-row-1-field" class="naboo-row-field naboo-sc-select-field">
							<option value="any" selected><?php esc_html_e( 'Article title, Abstract, Keywords', 'naboodatabase' ); ?></option>
						</select>
					</div>
					
					<div class="naboo-sc-input-wrap">
						<input type="text"
						       id="naboo-row-1-term"
						       class="naboo-row-term naboo-sc-input"
						       placeholder="<?php esc_attr_e( 'Search psychological scales, constructs, or authors...', 'naboodatabase' ); ?>"
						       autocomplete="off">
						<div class="naboo-suggestions-box" id="naboo-suggestions-1" style="display:none;"></div>
					</div>
				</div>
				<!-- Additional boolean rows injected by JS -->
			</div>
			
			<!-- Actions Below Search -->
			<div class="naboo-google-actions">
				<button type="button" id="naboo-submit-search" class="naboo-google-btn primary">
					<?php esc_html_e( 'Search', 'naboodatabase' ); ?>
				</button>
				<button type="button" id="naboo-toggle-advanced" class="naboo-google-btn">
					<?php esc_html_e( 'Advanced Search', 'naboodatabase' ); ?>
				</button>

				<?php if ( get_option( 'naboo_scale_index_enabled', 1 ) ) :
					$_idx_slug  = sanitize_title( get_option( 'naboo_scale_index_slug', 'scales-index' ) );
					$_idx_url   = home_url( '/' . $_idx_slug . '/' );
					$_idx_label = sanitize_text_field( get_option( 'naboo_scale_index_title', __( 'Scale Index', 'naboodatabase' ) ) );
				?>
				<a href="<?php echo esc_url( $_idx_url ); ?>"
				   class="naboo-google-btn naboo-scale-index-btn"
				   style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;justify-content:center;">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
					<?php echo esc_html( $_idx_label ); ?>
				</a>
				<?php endif; ?>

				<!-- Add Row / Date hidden behind advanced or logic, kept for structure -->
				<button type="button" id="naboo-add-row" style="display:none;">Add row</button>
				<button type="button" id="naboo-add-date" style="display:none;">Add date</button>
			</div>

			<div class="naboo-sc-links" style="display:none;">
				<div class="naboo-sc-links-right">
					<button type="button" id="naboo-clear-form" class="naboo-sc-clear-btn"><?php esc_html_e( 'Clear', 'naboodatabase' ); ?></button>
					<span class="naboo-filter-count-badge" id="naboo-filter-badge" style="display:none;">
						<span id="naboo-filter-count">0</span> <?php esc_html_e( 'filters', 'naboodatabase' ); ?>
					</span>
				</div>
			</div>

			<!-- Date range row (hidden by default, injected by Add date range btn) -->
			<div class="naboo-sc-date-row" id="naboo-date-range-row" style="display:none;">
				<label class="naboo-sc-date-label"><?php esc_html_e( 'Publication years', 'naboodatabase' ); ?></label>
				<div class="naboo-sc-date-inputs">
					<select id="naboo-year-from-preset" class="naboo-sc-select-sm">
						<option value=""><?php esc_html_e( 'From year', 'naboodatabase' ); ?></option>
						<?php for ( $y = $current_year; $y >= 1950; $y-- ) : ?>
							<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
						<?php endfor; ?>
					</select>
					<span class="naboo-dash">–</span>
					<select id="naboo-year-to" class="naboo-sc-select-sm">
						<option value=""><?php esc_html_e( 'To year', 'naboodatabase' ); ?></option>
						<?php for ( $y = $current_year; $y >= 1950; $y-- ) : ?>
							<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>

			<!-- Advanced filter panel (hidden, controlled by JS) -->
			<div class="naboo-sc-advanced" id="naboo-advanced-panel" style="display:none;">
				<div class="naboo-sc-adv-grid">

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-fstrip-category"><?php esc_html_e( 'Category', 'naboodatabase' ); ?></label>
						<select id="naboo-fstrip-category" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'All categories', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-fstrip-author"><?php esc_html_e( 'Scale Author', 'naboodatabase' ); ?></label>
						<select id="naboo-fstrip-author" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'All authors', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-filter-language"><?php esc_html_e( 'Language', 'naboodatabase' ); ?></label>
						<select id="naboo-filter-language" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'Any', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-filter-test-type"><?php esc_html_e( 'Instrument type', 'naboodatabase' ); ?></label>
						<select id="naboo-filter-test-type" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'Any', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-filter-age-group"><?php esc_html_e( 'Age group', 'naboodatabase' ); ?></label>
						<select id="naboo-filter-age-group" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'Any', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-filter-format"><?php esc_html_e( 'Response format', 'naboodatabase' ); ?></label>
						<select id="naboo-filter-format" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value=""><?php esc_html_e( 'Any', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label"><?php esc_html_e( 'Items range', 'naboodatabase' ); ?></label>
						<div class="naboo-sc-range">
							<input type="number" id="naboo-items-min" class="naboo-sc-range-input" placeholder="<?php esc_attr_e( 'Min', 'naboodatabase' ); ?>" min="0">
							<span class="naboo-dash">–</span>
							<input type="number" id="naboo-items-max" class="naboo-sc-range-input" placeholder="<?php esc_attr_e( 'Max', 'naboodatabase' ); ?>" min="0">
						</div>
					</div>

					<div class="naboo-sc-adv-group naboo-sc-adv-check">
						<label class="naboo-sc-adv-label"><?php esc_html_e( 'Download available', 'naboodatabase' ); ?></label>
						<label class="naboo-sc-toggle">
							<input type="checkbox" id="naboo-filter-has-file">
							<span class="naboo-sc-toggle-knob"></span>
						</label>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-sort"><?php esc_html_e( 'Sort by', 'naboodatabase' ); ?></label>
						<select id="naboo-sort" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value="date"><?php esc_html_e( 'Date added (newest)', 'naboodatabase' ); ?></option>
							<option value="relevance"><?php esc_html_e( 'Relevance', 'naboodatabase' ); ?></option>
							<option value="year_desc"><?php esc_html_e( 'Publication year ↓', 'naboodatabase' ); ?></option>
							<option value="year_asc"><?php esc_html_e( 'Publication year ↑', 'naboodatabase' ); ?></option>
							<option value="reliability_desc"><?php esc_html_e( 'Reliability ↓', 'naboodatabase' ); ?></option>
							<option value="validity_desc"><?php esc_html_e( 'Validity ↓', 'naboodatabase' ); ?></option>
							<option value="views"><?php esc_html_e( 'Most viewed', 'naboodatabase' ); ?></option>
							<option value="title_asc"><?php esc_html_e( 'Title A → Z', 'naboodatabase' ); ?></option>
						</select>
					</div>

					<div class="naboo-sc-adv-group">
						<label class="naboo-sc-adv-label" for="naboo-per-page"><?php esc_html_e( 'Results per page', 'naboodatabase' ); ?></label>
						<select id="naboo-per-page" class="naboo-sc-select-sm naboo-sc-select-full">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="50">50</option>
						</select>
					</div>

				</div><!-- /.naboo-sc-adv-grid -->
			</div><!-- /#naboo-advanced-panel -->

			<div class="naboo-sc-divider"></div>

			<!-- Search history hint -->
			<div class="naboo-sc-hint">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<?php esc_html_e( 'Results update instantly when you click Search.', 'naboodatabase' ); ?>
				<?php printf(
					'<span class="naboo-sc-stat-pill">%s %s</span> %s <span class="naboo-sc-stat-pill">%s %s</span>',
					number_format_i18n( $published ),
					esc_html__( 'scales', 'naboodatabase' ),
					esc_html__( 'across', 'naboodatabase' ),
					number_format_i18n( (int) $num_cats ),
					esc_html__( 'categories', 'naboodatabase' )
				); ?>
				<?php 
				$api_keys = get_option('naboo_gemini_api_key');
				$has_api_key = ! empty( $api_keys ) && ( is_array( $api_keys ) || is_string( $api_keys ) );
				if ( is_user_logged_in() && $has_api_key ) : ?>
					<button type="button" id="naboo-show-ai-upload" style="margin-left: 10px; font-size: 11px; padding: 2px 8px; background: transparent; border: 1px solid #d1d5db; border-radius: 4px; color: #4b5563; cursor: pointer; transition: all 0.2s;"><?php esc_html_e( 'Upload your scale', 'naboodatabase' ); ?></button>
				<?php endif; ?>
			</div>

			<!-- Quick Browse (SEO Boost) -->
			<div class="naboo-quick-browse" style="margin-top: 32px; border-top: 1px solid #f1f5f9; padding-top: 24px;">
				<h2 style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; text-align: center;">
					<?php esc_html_e( 'Quick Browse by Category', 'naboodatabase' ); ?>
				</h2>
				<div class="naboo-category-chips" style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;">
					<?php 
					$top_cats = get_terms( array(
						'taxonomy' => 'scale_category',
						'number'   => 8,
						'orderby'  => 'count',
						'order'    => 'DESC',
						'hide_empty' => true,
					) );
					if ( ! is_wp_error( $top_cats ) && ! empty( $top_cats ) ) :
						foreach ( $top_cats as $cat ) : ?>
							<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="naboo-cat-chip" style="padding: 6px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 13px; color: #475569; text-decoration: none; transition: all 0.2s;">
								<?php echo esc_html( $cat->name ); ?>
								<span style="font-size: 11px; color: #94a3b8; margin-left: 4px;">(<?php echo $cat->count; ?>)</span>
							</a>
						<?php endforeach;
					endif; ?>
				</div>
			</div>

			<!-- Recent Searches Container (Populated by JS) -->
			<div id="naboo-recent-searches-wrap" class="naboo-recent-wrap" style="display:none;">
				<div class="naboo-recent-header">
					<h3 class="naboo-recent-title"><?php esc_html_e( 'Recent searches', 'naboodatabase' ); ?></h3>
					<button type="button" id="naboo-clear-history-btn" class="naboo-recent-clear"><?php esc_html_e( 'Clear history', 'naboodatabase' ); ?></button>
				</div>
				<ul id="naboo-recent-list" class="naboo-recent-list"></ul>
			</div>

			<!-- AI Upload Wrapper (Hidden by default) -->
			<?php if ( is_user_logged_in() && $has_api_key ) : ?>
			<div id="naboo-ai-upload-modal-wrap" style="display:none; margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 40px;">
				<h2 style="font-family: var(--naboo-font-heading); font-size: 1.5rem; text-align: center; margin-bottom: 20px;">
					✨ <?php esc_html_e( 'AI Scale Extractor', 'naboodatabase' ); ?>
				</h2>
				<?php echo do_shortcode( '[naboo_ai_submit]' ); ?>
			</div>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					const toggleBtn = document.getElementById('naboo-show-ai-upload');
					const aiWrap = document.getElementById('naboo-ai-upload-modal-wrap');
					if(toggleBtn && aiWrap) {
						toggleBtn.addEventListener('click', function() {
							if(aiWrap.style.display === 'none') {
								aiWrap.style.display = 'block';
								// Smooth scroll to it
								aiWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
							} else {
								aiWrap.style.display = 'none';
							}
						});
					}
				});
			</script>
			<?php endif; ?>

		</div><!-- /.naboo-search-container -->
	</section><!-- /#naboo-screen-search -->

	<!-- ══════════════ SCREEN 2 : RESULTS ══════════════ -->
	<section class="naboo-screen naboo-screen-results" id="naboo-screen-results">


		<div class="naboo-results-body" id="naboo-search-results-wrapper">
			<!-- populated by advanced-search.js -->
		</div>

	</section><!-- /#naboo-screen-results -->

</div><!-- /.naboo-slide-track -->
</div><!-- /.naboo-slide-wrapper -->

<!-- Template for additional boolean rows -->
<template id="naboo-row-template">
	<div class="naboo-sc-row" data-row="__ROW__">
		<div class="naboo-sc-operator-wrap">
			<select class="naboo-row-operator naboo-sc-operator">
				<option value="AND">AND</option>
				<option value="OR">OR</option>
				<option value="NOT">NOT</option>
			</select>
		</div>
		<div class="naboo-sc-field-wrap">
			<label class="naboo-sc-field-label"><?php esc_html_e( 'Search within', 'naboodatabase' ); ?></label>
			<select class="naboo-row-field naboo-sc-select-field">
				<option value="any"><?php esc_html_e( 'Article title, Abstract, Keywords', 'naboodatabase' ); ?></option>
				<option value="title"><?php esc_html_e( 'Title', 'naboodatabase' ); ?></option>
				<option value="author"><?php esc_html_e( 'Author', 'naboodatabase' ); ?></option>
				<option value="construct"><?php esc_html_e( 'Construct / Concept', 'naboodatabase' ); ?></option>
				<option value="purpose"><?php esc_html_e( 'Purpose', 'naboodatabase' ); ?></option>
				<option value="abstract"><?php esc_html_e( 'Abstract', 'naboodatabase' ); ?></option>
				<option value="population"><?php esc_html_e( 'Population', 'naboodatabase' ); ?></option>
				<option value="references"><?php esc_html_e( 'References', 'naboodatabase' ); ?></option>
				<option value="content"><?php esc_html_e( 'Full Text', 'naboodatabase' ); ?></option>
			</select>
		</div>
		<div class="naboo-sc-input-wrap">
			<input type="text" class="naboo-row-term naboo-sc-input"
			       placeholder="<?php esc_attr_e( 'Search documents…', 'naboodatabase' ); ?>" autocomplete="off">
			<div class="naboo-suggestions-box" style="display:none;"></div>
		</div>
		<button type="button" class="naboo-remove-row" title="<?php esc_attr_e( 'Remove row', 'naboodatabase' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>
</template>
