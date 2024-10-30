<?php
/**
 * Provides a admin comment section area view for the plugin
 *
 * This file contains the HTML markup for displaying content approval feedbacks
 * in the WordPress admin area.
 *
 * @link  https://qrolic.com/
 * @since 1.0.0
 *
 * @package    Content_Approval_Workflow
 * @subpackage Content_Approval_Workflow/admin/partials
 */

?>

<div class="caw-feedbacks" id="caw-feedbacks">
	<?php // Display the heading with the total number of feedbacks. ?>
		<h2 class="caw-feedback-heading">
			<?php esc_html_e( 'Content Approval Feedback', 'content-approval-workflow' ); ?>
			(<span id="caw-feedback-count">
				<?php echo esc_html( $caw_total_feedbacks ); ?>
			</span>)
		</h2>
	<div class="caw-feedback-form">
		<div>
			<textarea name="feedback" id="caw-feedback-form-textarea" rows="3"></textarea>
			<div class="alert-message"></div>
		</div>
		<button id="caw-feedback-submit-btn" class="button-primary">
			<?php esc_html_e( 'Feedback', 'content-approval-workflow' ); ?>
		</button>
	</div>

	<div id="caw-feedbacks-container">
		<?php foreach ( $caw_feedbacks as $caw_feedback ) : ?>
			<?php
			// Get author information and avatar URL.
			$author_link = get_author_posts_url( get_user_by( 'email', $caw_feedback->comment_author_email )->ID );
			$avatar_url  = get_avatar_url( $caw_feedback->comment_author_email );
			?>
			<div class="caw-feedback">
				<div class="caw-avatar">
					<img alt="<?php echo esc_attr( $caw_feedback->comment_author . ' Avatar' ); ?>"
						src="<?php echo esc_url( $avatar_url ); ?>" class="avatar-image" height="50" width="50"
						style="border-radius:20px;" loading="lazy" />
				</div>

				<div>
					<div class="caw-feedback-author">
						<a href="<?php echo esc_url( $author_link ); ?>" class="caw-feedback-author-name">
							<?php echo esc_attr( $caw_feedback->comment_author ); ?>
						</a>
						<span class="caw-feedback-date">
							<?php
							// Display formatted date.
							echo esc_html( gmdate( 'j M h:i A', strtotime( $caw_feedback->comment_date ) ) );
							?>
						</span>
					</div>

					<div class="caw-feedback-content">
						<?php echo wp_kses_post( $caw_feedback->comment_content ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
		<?php if ( 10 < $caw_total_feedbacks ) : ?>
			<div class="center-align">
				<span id="load-more-feedbacks" class="button-link">
					<?php esc_html_e( 'Load More Feedbacks', 'content-approval-workflow' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>
</div>
