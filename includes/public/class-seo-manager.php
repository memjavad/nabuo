<?php
/**
 * SEO & Metadata Manager for Frontend
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

class SEO_Manager {

	/**
	 * Outputs Schema.org JSON-LD markup for psych_scale singular pages.
	 */
	public function add_schema_markup() {
		$seo_opts = get_option( 'naboodatabase_seo_options', array() );
		if ( isset( $seo_opts['enable_schema'] ) && ! $seo_opts['enable_schema'] ) {
			return;
		}

		// Homepage Schema: Organization & WebSite (SearchAction)
		if ( is_front_page() ) {
			$publisher_name = ! empty( $seo_opts['publisher_name'] ) ? $seo_opts['publisher_name'] : get_bloginfo('name');
			$publisher_logo = ! empty( $seo_opts['publisher_logo_url'] ) ? $seo_opts['publisher_logo_url'] : '';
			
			$org_schema = array(
				'@context' => 'https://schema.org',
				'@type' => 'Organization',
				'name' => $publisher_name,
				'url' => home_url(),
				'logo' => $publisher_logo,
				'sameAs' => array(
					'https://www.facebook.com/arabpsychology',
					'https://twitter.com/arabpsychology'
				)
			);
			
			$search_schema = array(
				'@context' => 'https://schema.org',
				'@type' => 'WebSite',
				'url' => home_url(),
				'potentialAction' => array(
					'@type' => 'SearchAction',
					'target' => home_url( '/search/?q={search_term_string}' ),
					'query-input' => 'required name=search_term_string'
				)
			);
			
			echo '<script type="application/ld+json">' . wp_json_encode( $org_schema ) . '</script>' . "\n";
			echo '<script type="application/ld+json">' . wp_json_encode( $search_schema ) . '</script>' . "\n";
			return;
		}

		// Taxonomy Archive Schema: BreadcrumbList
		if ( is_tax( array( 'scale_category', 'scale_author' ) ) ) {
			$term = get_queried_object();
			$breadcrumb = array(
				'@context' => 'https://schema.org',
				'@type' => 'BreadcrumbList',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 1, 'name' => __( 'Home', 'naboodatabase' ), 'item' => home_url() ),
					array( '@type' => 'ListItem', 'position' => 2, 'name' => $term->name, 'item' => get_term_link( $term ) )
				)
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb ) . '</script>' . "\n";
			return;
		}

		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$post = get_post();
		$terms = get_the_terms( $post->ID, 'scale_category' );
		$category = ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
		
		$authors = get_the_terms( $post->ID, 'scale_author' );
		$creators = array();
		if ( $authors && ! is_wp_error( $authors ) ) {
			foreach ( $authors as $author ) {
				$creators[] = array(
					'@type' => 'Person',
					'name'  => $author->name
				);
			}
		} else if ( ! empty( $seo_opts['default_author'] ) ) {
			$creators[] = array(
				'@type' => 'Person',
				'name'  => $seo_opts['default_author']
			);
		}

		$pub_year = get_post_meta( $post->ID, '_naboo_scale_year', true );
		$language = get_post_meta( $post->ID, '_naboo_scale_language', true );
		if ( empty( $language ) && ! empty( $seo_opts['default_language'] ) ) {
			$language = $seo_opts['default_language'];
		}

		$is_free = isset( $seo_opts['is_accessible_for_free'] ) ? (bool) $seo_opts['is_accessible_for_free'] : true;
		$license = ! empty( $seo_opts['default_license'] ) ? $seo_opts['default_license'] : 'https://creativecommons.org/licenses/by-nc/4.0/';
		$publisher_name = ! empty( $seo_opts['publisher_name'] ) ? $seo_opts['publisher_name'] : get_bloginfo('name');
		$publisher_logo = ! empty( $seo_opts['publisher_logo_url'] ) ? $seo_opts['publisher_logo_url'] : '';

		$desc = get_post_meta( $post->ID, '_naboo_scale_abstract', true );
		if ( empty( $desc ) ) {
			$desc = get_the_excerpt();
		}
		if ( empty( $desc ) ) {
			$desc = wp_trim_words( get_post_field( 'post_content', $post->ID ), 30, '...' );
		}
		if ( empty( $desc ) ) {
			$desc = get_the_title() . ' - ' . __( 'A psychological scale from the Naboo Database.', 'naboodatabase' );
		}
		$desc = wp_strip_all_tags( $desc );

		$image = '';
		if ( has_post_thumbnail( $post->ID ) ) {
			$image = get_the_post_thumbnail_url( $post->ID );
		} else if ( ! empty( $seo_opts['social_image_url'] ) ) {
			$image = $seo_opts['social_image_url'];
		} else if ( has_site_icon() ) {
			$image = get_site_icon_url();
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => array( 'Dataset', 'ScholarlyArticle', 'Book' ),
			'name'     => get_the_title(),
			'headline' => get_the_title(),
			'description' => $desc,
			'url'      => get_permalink(),
			'datePublished' => $pub_year ? $pub_year . '-01-01T00:00:00+00:00' : get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'creator'  => $creators,
			'author'   => $creators,
			'measurementTechnique' => 'Psychological Assessment',
			'variableMeasured' => implode( ', ', $category ),
			'inLanguage' => $language,
			'isAccessibleForFree' => $is_free,
			'license' => $license,
			'publisher' => array(
				'@type' => 'Organization',
				'name' => $publisher_name,
			)
		);

		if ( $image ) {
			$schema['image'] = $image;
		}

		$keywords_meta = get_post_meta( $post->ID, '_naboo_scale_keywords', true );
		if ( $keywords_meta ) {
			$clean_kw = wp_strip_all_tags( wp_specialchars_decode( $keywords_meta ) );
			$keywords = preg_replace( '/^(keywords|keyword):\s*/i', '', trim( $clean_kw ) );
			if ( ! empty( $keywords ) ) {
				$schema['keywords'] = $keywords;
			}
		}

		if ( $publisher_logo ) {
			$schema['publisher']['logo'] = array(
				'@type' => 'ImageObject',
				'url' => $publisher_logo
			);
		}

		// Add Ratings and Reviews
		global $wpdb;
		$ratings_table = $wpdb->prefix . 'naboo_ratings';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ratings_table ) ) === $ratings_table ) {
			$ratings_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM {$ratings_table} WHERE scale_id = %d AND status = 'approved'",
				$post->ID
			) );
			
			if ( $ratings_data && $ratings_data->count > 0 ) {
				$schema['aggregateRating'] = array(
					'@type' => 'AggregateRating',
					'ratingValue' => number_format( (float) $ratings_data->avg_rating, 1, '.', '' ),
					'reviewCount' => absint( $ratings_data->count ),
					'bestRating' => '5',
					'worstRating' => '1'
				);
			}
		}

		$comments_table = $wpdb->prefix . 'naboo_comments';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $comments_table ) ) === $comments_table ) {
			$reviews = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$comments_table} WHERE scale_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT 10",
				$post->ID
			) );

			if ( $reviews ) {
				$schema['review'] = array();
				foreach ( $reviews as $rev ) {
					$schema['review'][] = array(
						'@type' => 'Review',
						'author' => array(
							'@type' => 'Person',
							'name' => esc_html( $rev->author_name )
						),
						'datePublished' => gmdate( 'Y-m-d', strtotime( $rev->created_at ) ),
						'reviewBody' => wp_strip_all_tags( $rev->comment_content )
					);
				}
				$schema['review'] = array_values( array_filter( $schema['review'], function( $r ) {
					return ! empty( $r['author']['name'] ) && ! empty( $r['reviewBody'] );
				} ) );
				if ( empty( $schema['review'] ) ) {
					unset( $schema['review'] );
				}
			}
		}

		$num_items = get_post_meta( $post->ID, '_naboo_scale_items', true );
		if ( $num_items ) {
			$schema['numberOfItems'] = intval( $num_items );
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
		
		// Breadcrumb Schema
		$breadcrumb_items = array();
		$breadcrumb_items[] = array( 
			'@type'    => 'ListItem', 
			'position' => 1, 
			'name'     => __( 'Home', 'naboodatabase' ), 
			'item'     => home_url( '/' ) 
		);

		if ( is_tax( array( 'scale_category', 'scale_author' ) ) ) {
			$term = get_queried_object();
			$breadcrumb_items[] = array( 
				'@type'    => 'ListItem', 
				'position' => 2, 
				'name'     => $term->name, 
				'item'     => get_term_link( $term ) 
			);
		} elseif ( is_singular( 'psych_scale' ) ) {
			$categories = get_the_terms( get_the_ID(), 'scale_category' );
			$pos = 2;
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$cat = $categories[0];
				$breadcrumb_items[] = array( 
					'@type'    => 'ListItem', 
					'position' => $pos++, 
					'name'     => $cat->name, 
					'item'     => get_term_link( $cat ) 
				);
			}
			$breadcrumb_items[] = array( 
				'@type'    => 'ListItem', 
				'position' => $pos, 
				'name'     => get_the_title(), 
				'item'     => get_permalink() 
			);
		}

		if ( count( $breadcrumb_items ) > 1 ) {
			$breadcrumb = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $breadcrumb_items
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb ) . '</script>' . "\n";
		}

		// FAQ Schema
		$faq_fields = array(
			'purpose' => sprintf( __( 'What is the purpose of %s?', 'naboodatabase' ), get_the_title() ),
			'construct' => sprintf( __( 'What construct does %s measure?', 'naboodatabase' ), get_the_title() ),
			'population' => sprintf( __( 'Who is the target population for %s?', 'naboodatabase' ), get_the_title() ),
			'reliability' => sprintf( __( 'How reliable is %s?', 'naboodatabase' ), get_the_title() ),
			'validity' => sprintf( __( 'What is the validity of %s?', 'naboodatabase' ), get_the_title() ),
			'administration_method' => sprintf( __( 'How is %s administered?', 'naboodatabase' ), get_the_title() ),
			'items' => sprintf( __( 'How many items are in %s?', 'naboodatabase' ), get_the_title() ),
			'scoring_rules' => sprintf( __( 'What are the scoring rules for %s?', 'naboodatabase' ), get_the_title() )
		);

		$faq_questions = array();
		foreach ( $faq_fields as $meta_key => $question_text ) {
			$meta_value = get_post_meta( $post->ID, '_naboo_scale_' . $meta_key, true );
			if ( ! empty( $meta_value ) ) {
				$faq_questions[] = array(
					'@type' => 'Question',
					'name' => $question_text,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text' => wp_strip_all_tags( wp_specialchars_decode( $meta_value ) )
					)
				);
			}
		}

		if ( ! empty( $faq_questions ) ) {
			$faq_schema = array(
				'@context' => 'https://schema.org',
				'@type' => 'FAQPage',
				'mainEntity' => $faq_questions
			);
			echo '<script type="application/ld+json">' . wp_json_encode( $faq_schema ) . '</script>' . "\n";
		}
	}

	/**
	 * Outputs <meta name="description"> and robots meta tag.
	 */
	public function add_meta_description() {
		if ( is_front_page() || is_home() ) {
			$desc = get_bloginfo( 'description' );
			if ( empty( $desc ) ) {
				$desc = __( 'Naboo Database: A comprehensive repository of psychological scales, research tools, and academic resources.', 'naboodatabase' );
			}
		} elseif ( is_tax( array( 'scale_category', 'scale_author' ) ) ) {
			$term = get_queried_object();
			$desc = term_description();
			if ( empty( $desc ) ) {
				$desc = sprintf( __( 'Explore psychological scales related to %s in the Naboo Database.', 'naboodatabase' ), $term->name );
			}
		} elseif ( is_singular( 'psych_scale' ) ) {
			$desc = get_post_meta( get_the_ID(), '_naboo_scale_abstract', true );
			if ( empty( $desc ) ) { $desc = get_the_excerpt(); }
			if ( empty( $desc ) ) { $desc = wp_trim_words( get_post_field( 'post_content', get_the_ID() ), 30, '...' ); }
			if ( empty( $desc ) ) { $desc = get_the_title() . ' — ' . __( 'A psychological scale from the Naboo Database.', 'naboodatabase' ); }
		} else {
			return;
		}
		
		$desc = wp_strip_all_tags( $desc );
		if ( mb_strlen( $desc ) > 155 ) {
			$desc = mb_substr( $desc, 0, 155 );
			$last_space = mb_strrpos( $desc, ' ' );
			if ( $last_space !== false ) { $desc = mb_substr( $desc, 0, $last_space ); }
			$desc .= '...';
		}
		echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
		echo '<meta name="robots" content="index, follow, max-image-preview:large" />' . "\n";
	}

	/**
	 * Outputs OpenGraph and Twitter meta tags.
	 */
	public function add_opengraph_tags() {
		$title = '';
		$desc  = '';
		$url   = '';
		$type  = 'website';

		if ( is_front_page() || is_home() ) {
			$title = get_bloginfo( 'name' );
			$desc  = get_bloginfo( 'description' );
			$url   = home_url();
		} elseif ( is_tax( array( 'scale_category', 'scale_author' ) ) ) {
			$term  = get_queried_object();
			$title = $term->name;
			$desc  = term_description();
			$url   = get_term_link( $term );
		} elseif ( is_singular( 'psych_scale' ) ) {
			$title = get_the_title();
			$desc  = get_post_meta( get_the_ID(), '_naboo_scale_abstract', true );
			if ( empty( $desc ) ) { $desc = get_the_excerpt(); }
			if ( empty( $desc ) ) { $desc = wp_trim_words( get_post_field( 'post_content', get_the_ID() ), 30, '...' ); }
			$url   = get_permalink();
			$type  = 'article';
		} else {
			return;
		}

		if ( empty( $desc ) ) { $desc = $title . ' - ' . __( 'A psychological scale from the Naboo Database.', 'naboodatabase' ); }
		$desc = wp_strip_all_tags( $desc );
		
		$url = get_permalink();
		$site_name = get_bloginfo( 'name' );
		
		$image = '';
		if ( has_post_thumbnail() ) { $image = get_the_post_thumbnail_url(); }
		else if ( ! empty( $seo_opts['social_image_url'] ) ) { $image = $seo_opts['social_image_url']; }
		else if ( has_site_icon() ) { $image = get_site_icon_url(); }

		if ( $enable_og ) {
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
			echo '<meta property="og:type" content="article" />' . "\n";
			echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '" />' . "\n";
			if ( $image ) { echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n"; }
		}

		if ( $enable_tw ) {
			echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
			echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '" />' . "\n";
			if ( $image ) { echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n"; }
		}
	}

	/**
	 * Outputs Highwire / Google Scholar meta tags.
	 */
	public function add_academic_meta_tags() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$seo_opts = get_option( 'naboodatabase_seo_options', array() );
		if ( isset( $seo_opts['enable_scholar'] ) && ! $seo_opts['enable_scholar'] ) {
			return;
		}

		$post_id = get_the_ID();
		$publisher_name = ! empty( $seo_opts['publisher_name'] ) ? $seo_opts['publisher_name'] : get_bloginfo('name');
		$source_ref = get_post_meta( $post_id, '_naboo_scale_source_reference', true );
		$journal_title = $publisher_name; 
		if ( $source_ref && preg_match('/\.\s+([A-Z][^,\.]+(?:\s+[A-Za-z]+)*),\s*\d+/', $source_ref, $m ) ) {
			$journal_title = trim( $m[1] );
		}

		echo '<meta name="citation_title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
		echo '<meta name="citation_journal_title" content="' . esc_attr( $journal_title ) . '" />' . "\n";

		$pub_year = get_post_meta( $post_id, '_naboo_scale_year', true );
		echo '<meta name="citation_publication_date" content="' . esc_attr( $pub_year ? $pub_year . '-01-01' : get_the_date( 'Y-m-d' ) ) . '" />' . "\n";

		$authors = get_the_terms( $post_id, 'scale_author' );
		if ( $authors && ! is_wp_error( $authors ) ) {
			foreach ( $authors as $author ) {
				echo '<meta name="citation_author" content="' . esc_attr( $author->name ) . '" />' . "\n";
			}
		} else if ( ! empty( $seo_opts['default_author'] ) ) {
			echo '<meta name="citation_author" content="' . esc_attr( $seo_opts['default_author'] ) . '" />' . "\n";
		}

		$pdf_id = get_post_meta( $post_id, '_naboo_scale_file', true );
		if ( $pdf_id && ( $pdf_url = wp_get_attachment_url( $pdf_id ) ) ) {
			echo '<meta name="citation_pdf_url" content="' . esc_url( $pdf_url ) . '" />' . "\n";
		}
		echo '<meta name="citation_abstract_html_url" content="' . esc_url( get_permalink( $post_id ) ) . '" />' . "\n";
	}

	/**
	 * Outputs favicon and mobile icons.
	 */
	public function add_site_icons() {
		$options = get_option( 'naboodatabase_customizer_options', array() );
		if ( ! empty( $options['favicon_url'] ) ) {
			echo '<link rel="icon" href="' . esc_url( $options['favicon_url'] ) . '" sizes="any" />' . "\n";
		}
		if ( ! empty( $options['mobile_icon_url'] ) ) {
			echo '<link rel="apple-touch-icon" href="' . esc_url( $options['mobile_icon_url'] ) . '" />' . "\n";
		}
	}
}
