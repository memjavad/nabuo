<?php
/**
 * Content Manager for Frontend
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

use ArabPsychology\NabooDatabase\Public\Admin_Bar;

class Content_Manager {

	/**
	 * Tracks view count for psych_scale posts, skipping bots.
	 */
	public function track_views() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$bot_patterns = array(
			'bot', 'crawl', 'slurp', 'spider', 'archiver', 'mediapartners',
			'facebookexternalhit', 'linkedinbot', 'twitterbot', 'baiduspider',
			'yandexbot', 'duckduckbot', 'sogou', 'exabot', 'facebot',
		);
		$user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		foreach ( $bot_patterns as $bot ) {
			if ( strpos( $user_agent, $bot ) !== false ) { return; }
		}

		$post_id = get_the_ID();
		$views   = get_post_meta( $post_id, '_naboo_view_count', true );
		$views   = $views ? (int) $views : 0;
		$views++;
		update_post_meta( $post_id, '_naboo_view_count', $views );
	}

	/**
	 * Injects scholarly meta and AI scripts into singular psych_scale content.
	 */
	public function inject_scale_content( $content ) {
		if ( is_singular( 'psych_scale' ) && in_the_loop() && is_main_query() ) {
			$meta_html     = $this->get_meta_html();
			$linked_html   = $this->get_linked_versions_html();
			$inline_script = '';
			
			if ( current_user_can( 'edit_post', get_the_ID() ) ) {
				$inline_script = $this->get_inline_ai_script();
			}
			
			return $meta_html . $linked_html . $content . $inline_script;
		}
		return $content;
	}

	/**
	 * Hides the title on pages containing NABOO shortcodes.
	 */
	public function hide_title_on_shortcode_pages( $title, $id = null ) {
		if ( ! is_singular() || is_admin() ) {
			return $title;
		}

		$post_id = $id ? absint( $id ) : get_the_ID();
		if ( absint( $post_id ) !== absint( get_queried_object_id() ) ) {
			return $title;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $title;
		}

		$shortcodes = array( 'naboo_search', 'naboo_submit', 'naboo_dashboard' );
		foreach ( $shortcodes as $shortcode ) {
			if ( has_shortcode( $post->post_content, $shortcode ) ) { return ''; }
		}

		return $title;
	}

	private function get_inline_ai_script() {
		ob_start();
		?>
		<style>@keyframes naboo-spin { 100% { transform: rotate(360deg); } } .naboo-spin { animation: naboo-spin 1s linear infinite; }</style>
		<script>
		jQuery(document).ready(function($) {
			/* AI Refine */
			$('.naboo-inline-ai-refine-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this), field = $btn.data('field'), postId = $btn.data('post-id'), $valContainer = $('#naboo-val-' + field);
				if (!$valContainer.length && field !== 'title') return;
				var originalHtml = $btn.html();
				$btn.prop('disabled', true).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="naboo-spin"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>');
				$.ajax({
					url: naboo_ajax_obj.ajax_url, type: 'POST',
					data: { action: 'naboo_inline_ai_refine', nonce: naboo_ajax_obj.nonce, post_id: postId, field_name: field },
					success: function(response) {
						if (response.success) {
							if (field === 'title') { location.reload(); }
							else if (field === 'r_code') { var el = document.getElementById('naboo-val-r_code'); if (el) el.innerText = response.data.formatted_text; }
							else { $valContainer.html(response.data.formatted_text); }
						} else { alert(response.data.message || 'Error occurred.'); }
					}, complete: function() { $btn.prop('disabled', false).html(originalHtml); }
				});
			});
			/* Manual Edit */
			$('.naboo-inline-manual-edit-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this), field = $btn.data('field'), postId = $btn.data('post-id'), $valContainer = $('#naboo-val-' + field);
				if (!$valContainer.length && field !== 'title') return;
				if ($valContainer.find('.naboo-manual-edit-wrap').length > 0) return;
				var originalHtml = $btn.html();
				$btn.prop('disabled', true).html('<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="naboo-spin"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>');
				$.ajax({
					url: naboo_ajax_obj.ajax_url, type: 'POST',
					data: { action: 'naboo_get_raw_field_value', nonce: naboo_ajax_obj.nonce, post_id: postId, field_name: field },
					success: function(response) {
						if (response.success) {
							var rawVal = response.data.raw_text;
							$valContainer.data('old-html', $valContainer.html());
							var isShort = ['year', 'items', 'language', 'test_type', 'format', 'age_group', 'authors', 'category'].includes(field);
							var editHtml = '<div class="naboo-manual-edit-wrap" style="margin-top: 8px;">';
							editHtml += isShort ? '<input type="text" class="naboo-manual-input" style="width:100%; border:1px solid #cbd5e1; border-radius:4px; padding:6px 10px;" value="' + rawVal.replace(/"/g, '&quot;') + '">' : '<textarea style="width:100%; min-height:120px; border:1px solid #cbd5e1; border-radius:4px; padding:10px;">' + rawVal + '</textarea>';
							editHtml += '<div style="margin-top:8px; display:flex; gap:6px;"><button class="naboo-save-manual-edit" style="background:var(--sc-blue); color:#fff; border:none; border-radius:4px; padding:4px 12px; font-weight:600; cursor:pointer;">Save</button><button class="naboo-cancel-manual-edit" style="background:transparent; color:var(--sc-text-muted); border:1px solid #cbd5e1; border-radius:4px; padding:4px 12px;">Cancel</button></div></div>';
							$valContainer.html(editHtml);
							$valContainer.find('.naboo-save-manual-edit').on('click', function() {
								var newText = isShort ? $valContainer.find('input').val() : $valContainer.find('textarea').val();
								$(this).prop('disabled', true).text('Saving...');
								$.ajax({
									url: naboo_ajax_obj.ajax_url, type: 'POST',
									data: { action: 'naboo_inline_manual_edit', nonce: naboo_ajax_obj.nonce, post_id: postId, field_name: field, new_value: newText },
									success: function(saveRes) {
										if (saveRes.success) { $valContainer.html(field === 'r_code' ? '' : saveRes.data.formatted_text); if (field === 'r_code') $valContainer[0].innerText = saveRes.data.formatted_text; }
										else { alert(saveRes.data.message || 'Error.'); $valContainer.find('.naboo-save-manual-edit').prop('disabled', false).text('Save'); }
									}
								});
							});
							$valContainer.find('.naboo-cancel-manual-edit').on('click', function() { $valContainer.html($valContainer.data('old-html')); });
						}
					}, complete: function() { $btn.prop('disabled', false).html(originalHtml); }
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	private function get_meta_html() {
		$id = get_the_ID();
		$bar = new Admin_Bar();
		$html = '<div class="naboo-scholarly-layout"><div class="naboo-scholarly-main">';
		$is_admin = current_user_can( 'edit_post', $id );

		$fields = array(
			'abstract'      => __( 'Abstract', 'naboodatabase' ),
			'purpose'       => __( 'Purpose', 'naboodatabase' ),
			'construct'     => __( 'Construct(s) Measured', 'naboodatabase' ),
			'items_list'    => __( 'Scale Items', 'naboodatabase' ),
			'scoring_rules' => __( 'Scoring Rules', 'naboodatabase' ),
		);

		foreach ( $fields as $key => $label ) {
			$val = get_post_meta( $id, '_naboo_scale_' . $key, true );
			if ( $val || $is_admin ) {
				$html .= '<h2 class="naboo-scholarly-heading">' . esc_html( $label ) . $bar->get_admin_action_buttons( $key ) . '</h2>';
				$html .= '<div class="naboo-scholarly-text" id="naboo-val-' . esc_attr( $key ) . '">' . nl2br( wp_kses_post( $val ) ) . '</div>';
			}
		}

		$keywords = get_post_meta( $id, '_naboo_scale_keywords', true );
		if ( $keywords || $is_admin ) {
			$val = preg_replace( '/^(keywords|keyword):\s*/i', '', trim( wp_strip_all_tags( wp_specialchars_decode( $keywords ) ) ) );
			$html .= '<div style="margin-bottom: 1.5rem;"><strong>' . __( 'Keywords', 'naboodatabase' ) . $bar->get_admin_action_buttons('keywords') . ':</strong> <span id="naboo-val-keywords">' . esc_html( $val ) . '</span></div>';
		}

		$r_code = get_post_meta( $id, '_naboo_scale_r_code', true );
		if ( $r_code || $is_admin ) {
			$html .= '<h2 class="naboo-scholarly-heading">' . __( 'R Code Auto-Scoring', 'naboodatabase' ) . $bar->get_admin_action_buttons('r_code') . '</h2>';
			$html .= '<div class="naboo-code-wrapper" style="margin-bottom:24px; border:1px solid var(--naboo-border); border-radius:8px; overflow:hidden;">';
			$html .= '<div class="naboo-code-header" style="display:flex; justify-content:space-between; padding:10px 16px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">';
			$html .= '<span style="font-size:13px; font-weight:600;">R</span><div style="display:flex; gap:8px;">';
			$html .= '<button onclick="nabooCopyRCode(this)" style="padding:6px 12px; font-size:13px; cursor:pointer; background:transparent; border:1px solid #e2e8f0; border-radius:4px;">' . __( 'Copy', 'naboodatabase' ) . '</button>';
			$html .= '<button onclick="nabooDownloadRCode()" style="padding:6px 12px; font-size:13px; cursor:pointer; background:#4f46e5; color:#fff; border:none; border-radius:4px;">' . __( 'Download .R', 'naboodatabase' ) . '</button>';
			$html .= '</div></div><pre style="margin:0; padding:16px; background:#fafafa; overflow-x:auto; white-space:pre;"><code id="naboo-val-r_code">' . str_replace( array( "\r\n", "\r", "\n" ), '&#10;', esc_html( trim( $r_code ) ) ) . '</code></pre>';
			$html .= '<div id="naboo-raw-r-code" style="display:none;">' . esc_html( trim( $r_code ) ) . '</div></div>';
			$html .= '<script>function nabooCopyRCode(b){const c=document.getElementById("naboo-val-r_code").innerText;navigator.clipboard.writeText(c).then(()=>{const t=b.innerHTML;b.innerText="Copied!";setTimeout(()=>b.innerHTML=t,2000);});}function nabooDownloadRCode(){const c=document.getElementById("naboo-raw-r-code").textContent;const bl=new Blob([c],{type:"text/plain"});const u=window.URL.createObjectURL(bl);const a=document.createElement("a");a.href=u;a.download="scale-scoring.R";document.body.appendChild(a);a.click();window.URL.revokeObjectURL(u);document.body.removeChild(a);}</script>';
		}

		$psych_fields = array(
			array( 'key' => 'reliability', 'label' => __( 'Reliability', 'naboodatabase' ) ),
			array( 'key' => 'validity',    'label' => __( 'Validity', 'naboodatabase' ) ),
			array( 'key' => 'factor_analysis', 'label' => __( 'Factor Analysis', 'naboodatabase' ) ),
			array( 'key' => 'population', 'label' => __( 'Target Population', 'naboodatabase' ) ),
			array( 'key' => 'administration_method', 'label' => __( 'Administration Method', 'naboodatabase' ) ),
			array( 'key' => 'instrument_type', 'label' => __( 'Instrument Type', 'naboodatabase' ) ),
		);

		$has_psych = false; $psych_inner = '';
		foreach ( $psych_fields as $item ) {
			$v = get_post_meta( $id, '_naboo_scale_' . $item['key'], true );
			if ( $v || $is_admin ) {
				$psych_inner .= '<tr><th scope="row" style="vertical-align:top; text-align:left; padding:12px;">' . esc_html( $item['label'] ) . $bar->get_admin_action_buttons($item['key']) . '</th><td style="padding:12px;" id="naboo-val-' . esc_attr($item['key']) . '">' . nl2br( wp_kses_post( $v ) ) . '</td></tr>';
				$has_psych = true;
			}
		}
		if ( $has_psych ) { $html .= '<div class="naboo-scholarly-section"><h2 class="naboo-scholarly-heading">' . __( 'Psychometric Properties & Administration', 'naboodatabase' ) . '</h2><table style="width:100%; border-collapse:collapse; border:1px solid #e2e8f0;">' . $psych_inner . '</table></div>'; }

		$ref_fields = array(
			'permissions' => __( 'Permissions & Fee', 'naboodatabase' ),
			'source_reference' => __( 'Source Reference', 'naboodatabase' ),
		);
		foreach ( $ref_fields as $key => $label ) {
			$v = get_post_meta( $id, '_naboo_scale_' . $key, true );
			if ( $v || $is_admin ) {
				$html .= '<div class="naboo-scholarly-section"><h2 class="naboo-scholarly-heading">' . esc_html( $label ) . $bar->get_admin_action_buttons($key) . '</h2><div id="naboo-val-' . esc_attr($key) . '">' . nl2br( wp_kses_post( $v ) ) . '</div></div>';
			}
		}
		$refs = get_post_meta( $id, '_naboo_scale_references', true );
		if ( $refs ) { $html .= '<div class="naboo-scholarly-section"><h2 class="naboo-scholarly-heading">' . __( 'References', 'naboodatabase' ) . '</h2><div class="naboo-scholarly-references">' . nl2br( wp_kses_post( $refs ) ) . '</div></div>'; }

		$html .= '</div>'; // End main

		$html .= '<aside class="naboo-scholarly-sidebar">';
		$fid = get_post_meta( $id, '_naboo_scale_file', true );
		if ( $fid && ( $furl = wp_get_attachment_url( $fid ) ) ) {
			$html .= '<div class="naboo-scholarly-sidebar-block"><a href="' . esc_url( $furl ) . '" class="naboo-scholarly-btn" download style="display:block; text-align:center; padding:12px; background:#4f46e5; color:#fff; border-radius:8px; text-decoration:none;">' . __( 'Download Full Text', 'naboodatabase' ) . '</a></div>';
		}

		$html .= '<div class="naboo-scholarly-sidebar-block"><h4 class="naboo-scholarly-sidebar-title">' . __( 'Scale Information', 'naboodatabase' ) . '</h4><ul class="naboo-scholarly-meta-list" style="list-style:none; padding:0;">';
		$tax_meta = array(
			'year'      => array( 'tax' => 'scale_year', 'label' => __( 'Year', 'naboodatabase' ) ),
			'authors'   => array( 'tax' => 'scale_author', 'label' => __( 'Scale Author', 'naboodatabase' ) ),
			'category'  => array( 'tax' => 'scale_category', 'label' => __( 'Category', 'naboodatabase' ) ),
			'language'  => array( 'tax' => 'scale_language', 'label' => __( 'Language', 'naboodatabase' ) ),
			'test_type' => array( 'tax' => 'scale_test_type', 'label' => __( 'Test Type', 'naboodatabase' ) ),
			'format'    => array( 'tax' => 'scale_format', 'label' => __( 'Format', 'naboodatabase' ) ),
			'age_group' => array( 'tax' => 'scale_age_group', 'label' => __( 'Age Group', 'naboodatabase' ) ),
		);
		foreach ( $tax_meta as $key => $data ) {
			$tlist = get_the_term_list( $id, $data['tax'], '', ', ' );
			if ( ( ! is_wp_error( $tlist ) && ! empty( $tlist ) ) || $is_admin ) {
				$html .= '<li style="margin-bottom:8px;"><strong>' . esc_html( $data['label'] ) . $bar->get_admin_action_buttons($key) . ':</strong> <span id="naboo-val-' . esc_attr($key) . '">' . $tlist . '</span></li>';
			}
		}
		$itms = get_post_meta( $id, '_naboo_scale_items', true );
		if ( $itms || $is_admin ) { $val = preg_replace( '/^(number of items|items):\s*/i', '', trim( wp_strip_all_tags( wp_specialchars_decode( $itms ) ) ) ); $html .= '<li style="margin-bottom:8px;"><strong>' . __( 'Items', 'naboodatabase' ) . $bar->get_admin_action_buttons('items') . ':</strong> <span id="naboo-val-items">' . esc_html( $val ) . '</span></li>'; }
		$views = get_post_meta( $id, '_naboo_view_count', true );
		if ( $views ) { $html .= '<li><strong>' . __( 'Views', 'naboodatabase' ) . ':</strong> ' . number_format_i18n( intval( $views ) ) . '</li>'; }
		$html .= '</ul></div>';

		$auth_dtls = get_post_meta( $id, '_naboo_scale_author_details', true );
		$auth_eml  = get_post_meta( $id, '_naboo_scale_author_email', true );
		$auth_orc  = get_post_meta( $id, '_naboo_scale_author_orcid', true );
		if ( $auth_dtls || $auth_eml || $auth_orc || $is_admin ) {
			$html .= '<div class="naboo-scholarly-sidebar-block"><h4 class="naboo-scholarly-sidebar-title">' . __( 'Author Information', 'naboodatabase' ) . '</h4>';
			if ( $auth_dtls || $is_admin ) { $html .= '<div style="font-weight:600; font-size:0.9em; margin-bottom:0.25rem;">' . __( 'Author Details', 'naboodatabase' ) . $bar->get_admin_action_buttons('author_details') . '</div><div id="naboo-val-author_details" style="font-size:0.9em; margin-bottom:0.75rem; line-height:1.5;">' . nl2br( wp_kses_post( $auth_dtls ) ) . '</div>'; }
			if ( $auth_eml || $is_admin ) { $html .= '<div style="margin-bottom:8px;"><strong>' . __( 'Email', 'naboodatabase' ) . $bar->get_admin_action_buttons('author_email') . ':</strong> <span id="naboo-val-author_email"><a href="mailto:' . esc_attr( $auth_eml ) . '">' . esc_html( $auth_eml ) . '</a></span></div>'; }
			if ( $auth_orc || $is_admin ) {
				$os = preg_split('/[\s,]+/', trim( (string) $auth_orc ) ); $ol = array();
				foreach ($os as $o) if (!empty($o)) $ol[] = '<a href="https://orcid.org/'.esc_attr($o).'" target="_blank">'.esc_html($o).'</a>';
				$html .= '<div><strong>' . __( 'ORCID', 'naboodatabase' ) . $bar->get_admin_action_buttons('author_orcid') . ':</strong> <span id="naboo-val-author_orcid">' . implode(', ', $ol) . '</span></div>';
			}
			$html .= '</div>';
		}

		$html .= '</aside></div>';
		return $html;
	}

	private function get_linked_versions_html() {
		$id = get_the_ID();
		$linked = get_post_meta( $id, '_naboo_scale_linked_versions', true );
		if ( empty( $linked ) || ! is_array( $linked ) ) { return ''; }
		$html = '<div class="naboo-scholarly-linked-versions"><h2 class="naboo-scholarly-heading">' . __( 'Other Versions of this Scale', 'naboodatabase' ) . '</h2><ul class="naboo-scholarly-linked-list">';
		$valid = false;
		foreach ( $linked as $v ) {
			if ( empty( $v['id'] ) || ! ( $p = get_post( $v['id'] ) ) || $p->post_status !== 'publish' ) continue;
			$valid = true;
			$html .= '<li><span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px; margin-top:4px;"></span> <a href="' . esc_url( get_permalink( $p->ID ) ) . '">' . esc_html( get_the_title( $p->ID ) ) . '</a>' . ( ! empty( $v['type'] ) ? ' &mdash; <em>' . esc_html( $v['type'] ) . '</em>' : '' ) . '</li>';
		}
		$html .= '</ul></div>';
		return $valid ? $html : '';
	}
}
