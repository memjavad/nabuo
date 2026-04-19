<?php
/**
 * Search Guard Manager for Frontend
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

class Search_Guard {

	/**
	 * Replace the WordPress search form with an empty string.
	 *
	 * @return string
	 */
	public function disable_search_form() {
		return '';
	}

	/**
	 * Redirect WordPress /?s= search queries to the NABOO search page.
	 * Passes the original term as ?keyword= for the JS engine to auto-search.
	 */
	public function redirect_search_to_apa() {
		if ( ! is_search() || is_admin() ) {
			return;
		}

		$search_term    = get_search_query();
		$naboo_search_url = $this->get_search_page_url();

		if ( $naboo_search_url ) {
			if ( $search_term ) {
				$naboo_search_url = add_query_arg( 'keyword', rawurlencode( $search_term ), $naboo_search_url );
			}
			wp_safe_redirect( $naboo_search_url, 301 );
		} else {
			wp_safe_redirect( home_url( '/' ), 302 );
		}
		exit;
	}

	/**
	 * Get the URL of the page containing the [naboo_search] shortcode.
	 *
	 * @return string|false
	 */
	public function get_search_page_url() {
		$all_pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'cache_results'  => true,
		) );

		foreach ( $all_pages as $pid ) {
			$page = get_post( $pid );
			if ( $page && has_shortcode( $page->post_content, 'naboo_search' ) ) {
				return get_permalink( $pid );
			}
		}

		return false;
	}

	/**
	 * Prevent the default WP search query from hitting the DB.
	 *
	 * @param \WP_Query $query
	 */
	public function disable_search_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( $query->is_search() ) {
			$query->set( 'post__in', array( 0 ) );
		}
	}
}
