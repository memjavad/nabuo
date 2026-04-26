<?php

namespace ArabPsychology\NabooDatabase\Core;

use WP_Widget;

class Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'naboodatabase_search_widget', 
			__( 'Naboo Database Search', 'naboodatabase' ), 
			array( 'description' => __( 'A search form for the Psychological Scales Database', 'naboodatabase' ), ) 
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$mode  = ! empty( $instance['mode'] ) ? $instance['mode'] : 'search';
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
        
		if ( $mode === 'search' ) {
			echo '<div class="naboo-widget-search">';
			require plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'public/partials/search-form.php';
			echo '</div>';
		} else {
			$query_args = array(
				'post_type'      => 'psych_scale',
				'post_status'    => 'publish',
				'posts_per_page' => $count,
				'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS for widget list
			);

			if ( $mode === 'popular' ) {
				$query_args['meta_key'] = '_naboo_view_count';
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
			}

			$scales_query = new \WP_Query( $query_args );

			if ( $scales_query->have_posts() ) {
				echo '<ul class="naboo-widget-list">';
				while ( $scales_query->have_posts() ) {
					$scales_query->the_post();
					echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
				}
				echo '</ul>';
				wp_reset_postdata();
			} else {
				echo '<p>' . esc_html__( 'No scales found.', 'naboodatabase' ) . '</p>';
			}
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Search Scales', 'naboodatabase' );
		$mode  = ! empty( $instance['mode'] ) ? $instance['mode'] : 'search';
		$count = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'naboodatabase' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php _e( 'Mode:', 'naboodatabase' ); ?></label> 
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<option value="search" <?php selected( $mode, 'search' ); ?>><?php _e( 'Search Form', 'naboodatabase' ); ?></option>
				<option value="popular" <?php selected( $mode, 'popular' ); ?>><?php _e( 'Popular Scales', 'naboodatabase' ); ?></option>
				<option value="recent" <?php selected( $mode, 'recent' ); ?>><?php _e( 'Recent Scales', 'naboodatabase' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php _e( 'Number of items to show:', 'naboodatabase' ); ?></label> 
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $count ); ?>" size="3">
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['mode']  = ( ! empty( $new_instance['mode'] ) ) ? strip_tags( $new_instance['mode'] ) : 'search';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? absint( $new_instance['count'] ) : 5;
		return $instance;
	}

}
