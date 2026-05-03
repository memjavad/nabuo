<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_Query;

class User_Dashboard {

	public function render_dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . sprintf( __( 'Please <a href="%s">login</a> to view your dashboard.', 'naboodatabase' ), wp_login_url( get_permalink() ) ) . '</p>';
		}

		$user_id = get_current_user_id();
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

		$args = array(
			'post_type'	  => 'psych_scale',
			'post_status'	=> array( 'publish', 'pending', 'draft' ),
			'author'		 => $user_id,
			'posts_per_page' => 10,
			'paged'		  => $paged
		);

		$query = new WP_Query( $args );

		ob_start();
		?>
		<div class="naboo-user-dashboard" data-dashboard="true">
			<?php $this->render_dashboard_header(); ?>
			<?php $this->render_dashboard_nav(); ?>

			<?php $this->render_submissions_tab( $query ); ?>

			<?php do_action( 'naboo_after_user_dashboard' ); ?>
		</div>
		<?php
		$this->render_dashboard_scripts();
		$this->render_dashboard_styles();

		wp_reset_postdata();
		return ob_get_clean();
	}

	private function render_dashboard_header() {
		?>
			<div class="naboo-dashboard-header-main">
				<h2><?php _e( 'Dashboard', 'naboodatabase' ); ?></h2>
			</div>
		<?php
	}

	private function render_dashboard_nav() {
		?>
			<ul class="naboo-dashboard-nav">
				<li><a href="#submissions" class="active"><?php _e('My Submissions', 'naboodatabase'); ?></a></li>
				<!-- Collections tab will auto-inject via JS if Collections module is active -->
				<li><a href="#analytics"><?php _e('My Analytics', 'naboodatabase'); ?></a></li>
			</ul>
		<?php
	}

	private function render_submissions_tab( $query ) {
		?>
			<div id="naboo-dashboard-submissions" class="naboo-dashboard-section">
				<div class="naboo-dashboard-header">
					<h3><?php _e( 'My Submissions', 'naboodatabase' ); ?></h3>
				</div>

				<?php if ( $query->have_posts() ) : ?>
					<table class="naboo-dashboard-table">
						<thead>
							<tr>
								<th><?php _e( 'Title', 'naboodatabase' ); ?></th>
								<th><?php _e( 'Status', 'naboodatabase' ); ?></th>
								<th><?php _e( 'Date', 'naboodatabase' ); ?></th>
								<th><?php _e( 'Actions', 'naboodatabase' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php while ( $query->have_posts() ) : $query->the_post(); ?>
								<tr>
									<td>
										<?php if ( get_post_status() == 'publish' ) : ?>
											<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
										<?php else : ?>
											<?php the_title(); ?>
										<?php endif; ?>
									</td>
									<td>
										<?php
										$status = get_post_status();
										$status_label = $status;
										$class = 'naboo-status-' . $status;

										if ( $status == 'publish' ) $status_label = __( 'Published', 'naboodatabase' );
										elseif ( $status == 'pending' ) $status_label = __( 'Pending Review', 'naboodatabase' );
										elseif ( $status == 'draft' ) $status_label = __( 'Draft', 'naboodatabase' );

										echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $status_label ) . '</span>';
										?>
									</td>
									<td><?php echo get_the_date(); ?></td>
									<td>
										<div class="naboo-action-buttons">
											<a href="<?php echo esc_url( admin_url( 'post.php?post=' . get_the_ID() . '&action=edit' ) ); ?>" class="naboo-dash-btn edit-btn"><?php _e( 'Edit', 'naboodatabase' ); ?></a>
											<a href="<?php echo esc_url( get_delete_post_link( get_the_ID(), '', true ) ); ?>" class="naboo-dash-btn delete-btn" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this scale?', 'naboodatabase' ); ?>');"><?php _e( 'Delete', 'naboodatabase' ); ?></a>
										</div>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>

					<div class="naboo-pagination">
						<?php
						echo paginate_links( array(
							'base'	=> str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
							'format'  => '?paged=%#%',
							'current' => max( 1, get_query_var( 'paged' ) ),
							'total'   => $query->max_num_pages,
						) );
						?>
					</div>
				<?php else : ?>
					<p><?php _e( 'You haven\'t submitted any scales yet.', 'naboodatabase' ); ?></p>
				<?php endif; ?>
				<p style="margin-top: 16px;"><a href="<?php echo esc_url( home_url( '/submit-scale/' ) ); ?>" class="button"><?php _e( 'Submit New Scale', 'naboodatabase' ); ?></a></p>
			</div>
		<?php
	}

	private function render_dashboard_scripts() {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				var navLinks = document.querySelectorAll('.naboo-dashboard-nav a');
				var sections = document.querySelectorAll('.naboo-dashboard-section');

				navLinks.forEach(function(link) {
					link.addEventListener('click', function(e) {
						e.preventDefault();
						var targetId = this.getAttribute('href').substring(1);

						// Handle native tabs
						if (targetId === 'submissions') {
							navLinks.forEach(function(l) { l.classList.remove('active'); });
							this.classList.add('active');
							sections.forEach(function(s) { s.style.display = 'none'; });
							document.getElementById('naboo-dashboard-' + targetId).style.display = 'block';
						}
					});
				});
			});
		</script>
		<?php
	}

	private function render_dashboard_styles() {
		?>
		<style>
			.naboo-user-dashboard {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				color: #333;
				background-color: #fff;
				padding: 24px;
				border-radius: 12px;
				box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
			}
			.naboo-user-dashboard h2 {
				margin-top: 0;
				margin-bottom: 24px;
				font-size: 24px;
				color: #2c3e50;
			}
			.naboo-dashboard-nav {
				display: flex;
				gap: 20px;
				list-style: none;
				padding: 0;
				margin: 0 0 24px 0;
				border-bottom: 1px solid #e2e8f0;
			}
			.naboo-dashboard-nav a {
				display: block;
				padding: 12px 4px;
				color: #64748b;
				text-decoration: none;
				font-weight: 500;
				border-bottom: 2px solid transparent;
				margin-bottom: -1px;
				transition: all 0.2s ease;
			}
			.naboo-dashboard-nav a:hover, .naboo-dashboard-nav a.active {
				color: #3b82f6;
				border-bottom-color: #3b82f6;
			}
			.naboo-dashboard-header h3 {
				font-size: 18px;
				color: #1e293b;
				margin-bottom: 16px;
				margin-top: 0;
			}
			.naboo-dashboard-table {
				width: 100%;
				border-collapse: separate;
				border-spacing: 0;
				margin-bottom: 24px;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				overflow: hidden;
			}
			.naboo-dashboard-table th, .naboo-dashboard-table td {
				padding: 16px;
				border-bottom: 1px solid #e2e8f0;
				text-align: left;
				vertical-align: middle;
			}
			.naboo-dashboard-table th {
				background-color: #f8fafc;
				font-weight: 600;
				color: #475569;
				text-transform: uppercase;
				font-size: 13px;
				letter-spacing: 0.05em;
			}
			.naboo-dashboard-table tr:hover td {
				background-color: #f1f5f9;
			}
			.naboo-dashboard-table tr:last-child td {
				border-bottom: none;
			}
			.naboo-dashboard-table a {
				color: #3b82f6;
				text-decoration: none;
				font-weight: 500;
				transition: color 0.15s ease-in-out;
			}
			.naboo-dashboard-table a:hover {
				color: #2563eb;
				text-decoration: underline;
			}
			/* Status Badges */
			[class^="naboo-status-"] {
				display: inline-flex;
				align-items: center;
				padding: 4px 10px;
				border-radius: 9999px;
				font-size: 13px;
				font-weight: 500;
				line-height: 1.2;
			}
			.naboo-status-publish {
				background-color: #d1fae5;
				color: #065f46;
				border: 1px solid #a7f3d0;
			}
			.naboo-status-pending {
				background-color: #fef3c7;
				color: #92400e;
				border: 1px solid #fde68a;
			}
			.naboo-status-draft {
				background-color: #f1f5f9;
				color: #475569;
				border: 1px solid #e2e8f0;
			}
			/* Button */
			.naboo-user-dashboard .button {
				display: inline-block;
				background-color: #3b82f6;
				color: #fff;
				padding: 10px 20px;
				border-radius: 6px;
				text-decoration: none;
				font-weight: 500;
				transition: background-color 0.2s, transform 0.1s;
				border: none;
				cursor: pointer;
			}
			.naboo-user-dashboard .button:hover {
				background-color: #2563eb;
				color: #fff;
				transform: translateY(-1px);
			}
			.naboo-user-dashboard .button:active {
				transform: translateY(0);
			}
			/* Pagination */
			.naboo-pagination {
				display: flex;
				gap: 8px;
				margin-top: 16px;
				justify-content: center;
			}
			.naboo-pagination .page-numbers {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 36px;
				height: 36px;
				border-radius: 6px;
				border: 1px solid #e2e8f0;
				background-color: #fff;
				color: #475569;
				text-decoration: none;
				font-weight: 500;
				transition: all 0.2s;
			}
			.naboo-pagination .page-numbers:hover {
				background-color: #f1f5f9;
				color: #3b82f6;
				border-color: #cbd5e1;
			}
			.naboo-pagination .page-numbers.current {
				background-color: #3b82f6;
				color: #fff;
				border-color: #3b82f6;
			}
			.naboo-action-buttons {
				display: flex;
				gap: 6px;
			}
			.naboo-dash-btn {
				padding: 4px 8px;
				border-radius: 4px;
				font-size: 12px;
				font-weight: 500;
				text-decoration: none;
				transition: color 0.15s, background-color 0.15s;
			}
			.naboo-dash-btn.edit-btn {
				color: #2563eb;
				background-color: #eff6ff;
			}
			.naboo-dash-btn.edit-btn:hover {
				background-color: #dbeafe;
				text-decoration: none;
			}
			.naboo-dash-btn.delete-btn {
				color: #dc2626;
				background-color: #fef2f2;
			}
			.naboo-dash-btn.delete-btn:hover {
				background-color: #fee2e2;
				text-decoration: none;
			}
		</style>
		<?php
	}
}
